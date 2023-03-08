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

class CallOutSessionAPIHoldService
{
    public function __construct(
        private EntityManagerInterface      $entityManager,
        private ToModeratorWebsocketService $toModeratorWebsocketService,
        private DirectSendService           $directSendService,
        private TranslatorInterface         $translator,
        private ThemeService                $themeService,
    )
    {
    }

    public function timeout($sessionId): array
    {
        $calloutSession = $this->entityManager->getRepository(CalloutSession::class)->findCalloutSessionActive($sessionId);
        if (!$calloutSession) {
            return array('error' => true, 'reason' => 'NO_SESSION_ID_FOUND');
        }
        return $this->setCalloutSessionOnHold($calloutSession,CalloutSession::$TIMEOUT, $this->translator->trans('callout.message.timeout', array('name' => $calloutSession->getUser()->getFormatedName($this->themeService->getApplicationProperties('laf_showNameFrontend')))));

    }

    public function occupied($sessionId): array
    {
        $calloutSession = $this->entityManager->getRepository(CalloutSession::class)->findCalloutSessionActive($sessionId);
        if (!$calloutSession) {
            return array('error' => true, 'reason' => 'NO_SESSION_ID_FOUND');
        }
        return $this->setCalloutSessionOnHold($calloutSession,CalloutSession::$OCCUPIED, $this->translator->trans('callout.message.occupied', array('name' => $calloutSession->getUser()->getFormatedName($this->themeService->getApplicationProperties('laf_showNameFrontend')))));

    }

    public function ringing($sessionId): array
    {
        $calloutSession = $this->entityManager->getRepository(CalloutSession::class)->findCalloutSessionActive($sessionId);
        if (!$calloutSession) {
            return array('error' => true, 'reason' => 'NO_SESSION_ID_FOUND');
        }
        return $this->setRinging($calloutSession);

    }

    public function later($sessionId): array
    {
        $calloutSession = $this->entityManager->getRepository(CalloutSession::class)->findCalloutSessionActive($sessionId);
        if (!$calloutSession) {
            return array('error' => true, 'reason' => 'NO_SESSION_ID_FOUND');
        }
        return $this->setCalloutSessionOnHold($calloutSession,CalloutSession::$LATER, $this->translator->trans('callout.message.later', array('name' => $calloutSession->getUser()->getFormatedName($this->themeService->getApplicationProperties('laf_showNameFrontend')))));

    }

    public function setCalloutSessionOnHold(CalloutSession $calloutSession, $state, $message)
    {
        if ($calloutSession->getState() >= CalloutSession::$ON_HOLD || $calloutSession->getState() < CalloutSession::$DIALED){
            return array('error' => true, 'reason' => 'SESSION_NOT_IN_CORRECT_STATE');
        }
        $calloutSession->setState($state);
        $this->entityManager->persist($calloutSession);
        $this->entityManager->flush();
        $this->sendMessage($calloutSession->getRoom(), $message);
        $this->toModeratorWebsocketService->refreshLobbyByRoom($calloutSession->getRoom());
        $sipRaumnummer = $calloutSession->getRoom()->getCallerRoom();
        $pin = $this->entityManager->getRepository(CallerId::class)->findOneBy(array('room' => $calloutSession->getRoom(), 'user' => $calloutSession->getUser()));

        return array(
            'status' => 'ON_HOLD',
            'pin' => $pin->getCallerId(),
            'room_number' => $sipRaumnummer->getCallerId(),
            'links' => array()
        );
    }

    public function setRinging(CalloutSession $calloutSession,){
        if ($calloutSession->getState() >= CalloutSession::$ON_HOLD){
            return array('error' => true, 'reason' => 'SESSION_NOT_IN_CORRECT_STATE');
        }
        $calloutSession->setState(CalloutSession::$RINGING);
        $this->entityManager->persist($calloutSession);
        $this->entityManager->flush();
        $this->toModeratorWebsocketService->refreshLobbyByRoom($calloutSession->getRoom());
        $pin = $this->entityManager->getRepository(CallerId::class)->findOneBy(array('room' => $calloutSession->getRoom(), 'user' => $calloutSession->getUser()));
        $sipRaumnummer = $calloutSession->getRoom()->getCallerRoom();
        return array(
            'status' => 'RINGING',
            'pin' => $pin->getCallerId(),
            'room_number' => $sipRaumnummer->getCallerId(),
            'links' => array()
        );

    }
    public function sendMessage(Rooms $room, $message)
    {
        $topic = 'lobby_moderator/' . $room->getUidReal();
        $this->directSendService->sendSnackbar($topic, $message, 'info');
    }


}