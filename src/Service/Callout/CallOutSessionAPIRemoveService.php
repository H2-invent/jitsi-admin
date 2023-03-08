<?php

namespace App\Service\Callout;

use App\Entity\CallerId;
use App\Entity\CalloutSession;
use App\Entity\Rooms;
use App\Service\Lobby\DirectSendService;
use App\Service\Lobby\ToModeratorWebsocketService;
use App\Service\RoomAddService;
use App\Service\RoomService;
use App\Service\ThemeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CallOutSessionAPIRemoveService
{
    public function __construct(
        private EntityManagerInterface      $entityManager,
        private ToModeratorWebsocketService $toModeratorWebsocketService,
        private RoomAddService              $roomAddService,
        private DirectSendService           $directSendService,
        private TranslatorInterface         $translator,
        private ThemeService                $themeService,
    )
    {
    }

    public function refuse($sessionId): array
    {
        $calloutSession = $this->entityManager->getRepository(CalloutSession::class)->findCalloutSessionActive( $sessionId);
        if (!$calloutSession) {
            return array('error' => true, 'reason' => 'NO_SESSION_ID_FOUND');
        }
        return $this->removeCalloutSession($calloutSession,
            $this->translator->trans('callout.message.refuse',
                array('name' => $calloutSession->getUser()->getFormatedName($this->themeService->getApplicationProperties('laf_showNameFrontend')))
            )
        );
    }

    public function error($sessionId): array
    {
        $calloutSession = $this->entityManager->getRepository(CalloutSession::class)->findCalloutSessionActive( $sessionId);
        if (!$calloutSession) {
            return array('error' => true, 'reason' => 'NO_SESSION_ID_FOUND');
        }
        return $this->removeCalloutSession($calloutSession,
            $this->translator->trans('callout.message.error',
                array('name' => $calloutSession->getUser()->getFormatedName($this->themeService->getApplicationProperties('laf_showNameFrontend')))
            )
        );
    }

    public function removeCalloutSession(?CalloutSession $calloutSession, $message)
    {

        $this->entityManager->remove($calloutSession);
        $this->entityManager->flush();
        $this->toModeratorWebsocketService->refreshLobbyByRoom($calloutSession->getRoom());
        $this->sendRefuseMessage($calloutSession->getRoom(), $message);
        $this->roomAddService->removeUserFromRoomNoRepeat($calloutSession->getRoom(), $calloutSession->getUser());
        $res = array(
            'status' => 'DELETED',
            'links' => array()
        );
        return $res;
    }

    public function sendRefuseMessage(Rooms $room, $message)
    {
        $topic = 'lobby_moderator/' . $room->getUidReal();
        $this->directSendService->sendSnackbar($topic, $message, 'danger');
    }

}