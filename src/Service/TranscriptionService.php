<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Rooms;
use App\Entity\Transcription;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class TranscriptionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerService $mailerService,
        private readonly Environment $twig,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function addNewTranscription(Rooms $room, string $text): Transcription
    {
        $transcription = (new Transcription())
            ->setRoom($room)
            ->setText($text)
        ;
        $this->entityManager->persist($transcription);
        $this->entityManager->flush();

        $this->sendTranscriptionReadyNotification($room, $transcription);

        return $transcription;
    }

    private function sendTranscriptionReadyNotification(Rooms $room, Transcription $transcription): void
    {
        $moderator = $room->getModerator();
        if ($moderator === null) {
            return;
        }

        $subject = $this->translator->trans('transcription.ready.subject', ['{roomName}' => $room->getName()]);
        $content = $this->twig->render('email/newTranscriptionReady.twig', ['room' => $room, 'transcription' => $transcription]);

        $this->mailerService->sendEmail(
            user: $moderator,
            betreff: $subject,
            content: $content,
            server: $room->getServer(),
            rooms: $room
        );
    }
}
