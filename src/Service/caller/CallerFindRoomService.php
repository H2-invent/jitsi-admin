<?php

namespace App\Service\caller;

use App\Entity\CallerRoom;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CallerFindRoomService
{
    private $em;
    private $urlGen;
    public function __construct(
        UrlGeneratorInterface        $urlGenerator,
        EntityManagerInterface       $entityManager,
        private ThemeService         $themeService,
        private SeriesDialInResolver $seriesDialInResolver,
    )
    {
        $this->urlGen = $urlGenerator;
        $this->em = $entityManager;
    }

    public function findRoom($id)
    {
        $caller = $this->em->getRepository(CallerRoom::class)->findOneBy(['callerId' => $id]);
        $now = (new \DateTime())->getTimestamp();
        if (!$caller) {
            return ['status' => 'ROOM_ID_UKNOWN', 'reason' => 'ROOM_ID_UKNOWN', 'links' => []];
        }
        $room = $this->seriesDialInResolver->resolveRoom($caller->getRoom());

        // Phone participants can not decrypt E2EE media, so the dial-in is refused before any other check.
        if ($room->isE2EEActive()) {
            return [
                'status' => 'HANGUP',
                'reason' => 'E2EE_ENABLED',
                'e2ee_enabled' => true,
                'startTime' => $room->getStartTimestamp(),
                'endTime' => $room->getEndTimestamp(),
                'links' => []
            ];
        }

        if ($room->getStartTimestamp() - 1800 > $now && $room->getPersistantRoom() !== true) {
            return [
                'status' => 'HANGUP',
                'reason' => 'TO_EARLY',
                'startTime' => $room->getStartTimestamp(),
                'endTime' => $room->getEndTimestamp(),
                'links' => []
            ];
        }

        if ($room->getEndTimestamp() < $now && $room->getPersistantRoom() !== true) {
            return [
                'status' => 'HANGUP',
                'reason' => 'TO_LATE',
                'startTime' => $room->getStartTimestamp(),
                'endTime' => $room->getEndTimestamp(),
                'links' => []
            ];
        }

        $lobbyEnabled = (bool)$room->getLobby();
        $totalOpenRooms = (bool)$room->getTotalOpenRooms();
        $personalPinEnabled = $this->themeService->getApplicationProperties('SIP_CALLER_SHOW_IN_FRONTEND') == 1;

        if ($lobbyEnabled && !$totalOpenRooms && !$personalPinEnabled) {
            return [
                'status' => 'HANGUP',
                'reason' => 'NO_PIN_CONFIGURED',
                'startTime' => $room->getStartTimestamp(),
                'endTime' => $room->getEndTimestamp(),
                'links' => []
            ];
        }

        return [
            'status' => 'ACCEPTED',
            'startTime' => $room->getStartTimestamp(),
            'endTime' => $room->getEndTimestamp(),
            'roomName' => $room->getName(),
            'lobby_enabled' => $lobbyEnabled,
            'e2ee_enabled' => false,
            'total_open_rooms' => $totalOpenRooms,
            'pin_required' => $lobbyEnabled && !$totalOpenRooms,
            'links' => $lobbyEnabled
                ? ['pin' => $this->urlGen->generate('caller_protected', ['roomId' => $id])]
                : ['open' => $this->urlGen->generate('caller_open', ['roomId' => $id])]
        ];
    }
}
