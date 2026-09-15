<?php

namespace App\Service\caller;

use App\Entity\CallerRoom;
use App\Service\Theme\ThemeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CallerFindRoomService
{
    private $em;
    private $urlGen;
    public function __construct(
        UrlGeneratorInterface  $urlGenerator,
        EntityManagerInterface $entityManager,
        private ThemeService   $themeService,
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

        if ($caller->getRoom()->getStartTimestamp() - 1800 > $now && $caller->getRoom()->getPersistantRoom() !== true) {
            return [
                'status' => 'HANGUP',
                'reason' => 'TO_EARLY',
                'startTime' => $caller->getRoom()->getStartTimestamp(),
                'endTime' => $caller->getRoom()->getEndTimestamp(),
                'links' => []
            ];
        }
        if ($caller->getRoom()->getEndTimestamp() < $now && $caller->getRoom()->getPersistantRoom() !== true) {
            return [
                'status' => 'HANGUP',
                'reason' => 'TO_LATE',
                'startTime' => $caller->getRoom()->getStartTimestamp(),
                'endTime' => $caller->getRoom()->getEndTimestamp(),
                'links' => []
            ];
        }
        $lobbyEnabled = (bool)$caller->getRoom()->getLobby();
        $personalPinEnabled = $this->themeService->getApplicationProperties('SIP_CALLER_SHOW_IN_FRONTEND') == 1;

        // If the lobby is enabled but no personal PIN is exposed, the protected flow cannot be completed
        if ($lobbyEnabled && !$personalPinEnabled) {
            return [
                'status' => 'HANGUP',
                'reason' => 'NO_PIN_CONFIGURED',
                'startTime' => $caller->getRoom()->getStartTimestamp(),
                'endTime' => $caller->getRoom()->getEndTimestamp(),
                'links' => []
            ];
        }

        return [
            'status' => 'ACCEPTED',
            'startTime' => $caller->getRoom()->getStartTimestamp(),
            'endTime' => $caller->getRoom()->getEndTimestamp(),
            'roomName' => $caller->getRoom()->getName(),
            'lobby_enabled' => $lobbyEnabled,
            'links' => $lobbyEnabled
                ? ['pin' => $this->urlGen->generate('caller_protected', ['roomId' => $id])]
                : ['open' => $this->urlGen->generate('caller_open', ['roomId' => $id])]
        ];
    }
}
