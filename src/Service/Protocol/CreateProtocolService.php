<?php

namespace App\Service\Protocol;

use App\Entity\ProtocolTemplate;
use App\Entity\Rooms;
use App\Entity\RoomStatus;
use App\Entity\RoomStatusParticipant;
use App\Entity\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Builds the attendance protocol of a room: every opening period of the room
 * (open/close time) with every entry/leave of each participant, rendered as PDF
 * using the server's protocol template (header, footer, logo).
 */
class CreateProtocolService
{
    private const array ALLOWED_LOGO_MIME_TYPES = ['image/png', 'image/jpeg', 'image/gif'];

    public function __construct(
        private readonly Environment         $environment,
        private readonly StorageInterface    $storage,
        private readonly KernelInterface     $appKernel,
        private readonly LoggerInterface     $logger,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function createProtocolPdf(Rooms $room, ?User $user): Dompdf
    {
        $fontDirectory = $this->appKernel->getProjectDir()
                         . DIRECTORY_SEPARATOR . 'var'
                         . DIRECTORY_SEPARATOR . 'cache';

        $options = new Options();
        $options->set('defaultFont', 'Roboto');
        $options->set('fontDir', $fontDirectory);
        $options->set('fontCache', $fontDirectory);
        $options->set('chroot', $fontDirectory);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->createProtocolHtml($room, $user));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $this->addPageNumbers($dompdf);

        return $dompdf;
    }

    private function addPageNumbers(Dompdf $dompdf): void
    {
        $canvas = $dompdf->getCanvas();
        $font   = $dompdf->getFontMetrics()->getFont('Roboto')
                  ?? $dompdf->getFontMetrics()->getFont('helvetica');
        $text   = $this->translator->trans(
            'protocol.pdf.page',
            ['{page}' => '{PAGE_NUM}', '{pages}' => '{PAGE_COUNT}']
        );

        $canvas->page_text(
            $canvas->get_width() - 110,
            $canvas->get_height() - 40,
            $text,
            $font,
            8,
            [0.42, 0.45, 0.5]
        );
    }

    public function createProtocolHtml(Rooms $room, ?User $user): string
    {
        $template = $room->getServer()?->getProtocolTemplate();

        $statuses = $room->getRoomstatuses()->toArray();
        usort(
            $statuses,
            fn(RoomStatus $a, RoomStatus $b) => $b->getRoomCreatedAt() <=> $a->getRoomCreatedAt()
        );

        return $this->environment->render('documents/protocol/template.html.twig', [
            'title'    => $room->getName(),
            'room'     => $room,
            'template' => $template,
            'logo'     => $this->createLogoDataUri($template),
            'timezone' => $user?->getTimeZone() ?: new \DateTimeImmutable()->getTimezone()->getName(),
            'sessions' => array_map(fn(RoomStatus $status) => $this->buildSession($status, $user), $statuses),
        ]);
    }

    /**
     * @return array{
     *     openedAt: ?\DateTimeImmutable,
     *     closedAt: ?\DateTimeImmutable,
     *     duration: ?string,
     *     participants: list<array{
     *         name: string,
     *         visits: list<array{enteredAt: ?\DateTimeImmutable, leftAt: ?\DateTimeImmutable, duration: ?string}>,
     *         totalDuration: string
     *     }>
     * }
     */
    public function buildSession(RoomStatus $status, ?User $user): array
    {
        $openedAt = $status->getRoomCreatedAtwithTimeZone($user);
        $closedAt = $status->getDestroyedAt() ? $status->getDestroyedAtwithTimeZone($user) : null;

        $visits = $status->getRoomStatusParticipants()->toArray();
        usort(
            $visits,
            fn(RoomStatusParticipant $a, RoomStatusParticipant $b) => $a->getEnteredRoomAt() <=> $b->getEnteredRoomAt()
        );

        // Participants are only identified by their display name, so all visits
        // (entry/leave pairs) with the same name are grouped together.
        $groups = [];
        foreach ($visits as $visit) {
            $name = trim(html_entity_decode(strip_tags((string)$visit->getParticipantName())));
            $key  = mb_strtolower($name);
            if (!isset($groups[$key])) {
                $groups[$key] = ['name' => $name, 'visits' => [], 'seconds' => 0];
            }

            $enteredAt = $visit->getEnteredRoomAtwithTimeZone($user);
            $leftAt    = $visit->getInRoom() ? null : $visit->getLeftRoomAtwithTimeZone($user);
            $seconds   = null;
            if ($enteredAt && $leftAt) {
                $seconds = max(0, $leftAt->getTimestamp() - $enteredAt->getTimestamp());
                $groups[$key]['seconds'] += $seconds;
            }

            $groups[$key]['visits'][]  = [
                'enteredAt' => $enteredAt,
                'leftAt'    => $leftAt,
                'duration'  => $seconds !== null ? self::formatDuration($seconds) : null,
            ];
        }

        $participants = [];
        foreach ($groups as $group) {
            $participants[] = [
                'name'          => $group['name'],
                'visits'        => $group['visits'],
                'totalDuration' => self::formatDuration($group['seconds']),
            ];
        }

        return [
            'openedAt'     => $openedAt,
            'closedAt'     => $closedAt,
            'duration'     => $openedAt && $closedAt
                ? self::formatDuration(max(0, $closedAt->getTimestamp() - $openedAt->getTimestamp()))
                : null,
            'participants' => $participants,
        ];
    }

    /**
     * Returns the template logo as data URI. Dompdf runs chrooted and without remote
     * access, so the image has to be embedded instead of being referenced by URL.
     */
    public function createLogoDataUri(?ProtocolTemplate $template): ?string
    {
        if (!$template || !$template->getLogo()?->getName()) {
            return null;
        }

        try {
            $stream = $this->storage->resolveStream($template, 'logoFile');
            if (!$stream) {
                return null;
            }
            $content = stream_get_contents($stream);
            fclose($stream);
            if (!$content) {
                return null;
            }

            // The type is validated on upload already; re-check the stored content with a
            // PHP core function (no fileinfo extension needed) before embedding it.
            $mimeType = getimagesizefromstring($content)['mime'] ?? null;
            if (!in_array($mimeType, self::ALLOWED_LOGO_MIME_TYPES, true)) {
                $this->logger->warning('Protocol logo has unsupported mime type', ['mimeType' => $mimeType]);
                return null;
            }

            return 'data:' . $mimeType . ';base64,' . base64_encode($content);
        } catch (\Throwable $exception) {
            $this->logger->warning('Protocol logo could not be loaded: ' . $exception->getMessage());
        }

        return null;
    }

    public static function formatDuration(int $seconds): string
    {
        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }
}
