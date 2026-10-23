<?php

namespace App\Service\Caller;

use App\Entity\CallerRoom;
use App\Service\Theme\ThemeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CallerFindRoomService
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly EntityManagerInterface $em,
        private readonly ThemeService $themeService,
    )
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function findRoom(string $id): array
    {
        $caller = $this->em->getRepository(CallerRoom::class)->findOneBy(['callerId' => $id]);
        $now    = new \DateTimeImmutable()->getTimestamp();
        if (!$caller) {
            return ['status' => 'ROOM_ID_UKNOWN', 'reason' => 'ROOM_ID_UKNOWN', 'links' => []];
        }

        // Phone participants can not decrypt E2EE media, so the dial-in is refused before any other check.
        if ($caller->getRoom()->isE2EEActive()) {
            return [
                'status'       => 'HANGUP',
                'reason'       => 'E2EE_ENABLED',
                'e2ee_enabled' => true,
                'startTime'    => $caller->getRoom()->getStartTimestamp(),
                'endTime'      => $caller->getRoom()->getEndTimestamp(),
                'links'        => []
            ];
        }

        if ($caller->getRoom()->getStartTimestamp() - 1800 > $now && $caller->getRoom()->getPersistantRoom() !== true) {
            return [
                'status'    => 'HANGUP',
                'reason'    => 'TO_EARLY',
                'startTime' => $caller->getRoom()->getStartTimestamp(),
                'endTime'   => $caller->getRoom()->getEndTimestamp(),
                'links'     => []
            ];
        }
        if ($caller->getRoom()->getEndTimestamp() < $now && $caller->getRoom()->getPersistantRoom() !== true) {
            return [
                'status'    => 'HANGUP',
                'reason'    => 'TO_LATE',
                'startTime' => $caller->getRoom()->getStartTimestamp(),
                'endTime'   => $caller->getRoom()->getEndTimestamp(),
                'links'     => []
            ];
        }
        $lobbyEnabled = (bool)$caller->getRoom()->getLobby();
        $totalOpenRooms = (bool)$caller->getRoom()->getTotalOpenRooms();
        $personalPinEnabled = $this->themeService->getApplicationProperties('SIP_CALLER_SHOW_IN_FRONTEND') == 1;

        if ($lobbyEnabled && !$totalOpenRooms && !$personalPinEnabled) {
            return [
                'status'    => 'HANGUP',
                'reason'    => 'NO_PIN_CONFIGURED',
                'startTime' => $caller->getRoom()->getStartTimestamp(),
                'endTime'   => $caller->getRoom()->getEndTimestamp(),
                'links'     => []
            ];
        }

        return [
            'status'           => 'ACCEPTED',
            'startTime'        => $caller->getRoom()->getStartTimestamp(),
            'endTime'          => $caller->getRoom()->getEndTimestamp(),
            'roomName'         => $caller->getRoom()->getName(),
            'endTime'          => $caller->getRoom()->getEndTimestamp(),
            'roomName'         => $caller->getRoom()->getName(),
            'lobby_enabled'    => $lobbyEnabled,
            'e2ee_enabled'     => false,
            'total_open_rooms' => $totalOpenRooms,
            'pin_required'     => $lobbyEnabled && !$totalOpenRooms,
            'links'            => $lobbyEnabled
                ? ['pin' => $this->urlGenerator->generate('caller_protected', ['roomId' => $id])]
                : ['open' => $this->urlGenerator->generate('caller_open', ['roomId' => $id])]
        ];
    }
}
