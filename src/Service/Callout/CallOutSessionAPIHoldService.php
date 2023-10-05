<?php

namespace App\Service\Callout;

use App\Entity\CallerId;
use App\Entity\CalloutSession;
use App\Entity\Rooms;
use App\Service\Lobby\DirectSendService;
use App\Service\Lobby\ToModeratorWebsocketService;
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
        private UrlGeneratorInterface       $urlGenerator,
    )
    {
    }

    /**
     * @param $sessionId
     * @return array
     * This Function is used when the Caller is not able to reach the invited user and the phone rings over a certain time.
     */
    public function timeout($sessionId): array
    {
        $calloutSession = $this->entityManager->getRepository(CalloutSession::class)->findCalloutSessionActive($sessionId);
        if (!$calloutSession) {
            return ['error' => true, 'reason' => 'NO_SESSION_ID_FOUND'];
        }
        return $this->setCalloutSessionOnHold($calloutSession, CalloutSession::$TIMEOUT, $this->translator->trans('callout.message.timeout', ['name' => $calloutSession->getUser()->getFormatedName($this->themeService->getApplicationProperties('laf_showNameFrontend'))]));
    }

    /**
     * @param $sessionId
     * @return array
     * This funktion is called when the called uder is occuppied so his ohone retuns  a occupied signal then the caller can trigger this funkction
     */
    public function occupied($sessionId): array
    {
        $calloutSession = $this->entityManager->getRepository(CalloutSession::class)->findCalloutSessionActive($sessionId);
        if (!$calloutSession) {
            return ['error' => true, 'reason' => 'NO_SESSION_ID_FOUND'];
        }
        return $this->setCalloutSessionOnHold($calloutSession, CalloutSession::$OCCUPIED, $this->translator->trans('callout.message.occupied', ['name' => $calloutSession->getUser()->getFormatedName($this->themeService->getApplicationProperties('laf_showNameFrontend'))]));
    }


    /**
     * @param $sessionId
     * @return array
     * The called user is selecting later by pressing a kex on his phone. The caller system has to trigger this function.
     * This function retuns the information for the called person to join the meeting later. this is the caller id and the pin for this meeting.
     * The inviting user is informed that the called user is joing later
     */
    public function later($sessionId): array
    {
        $calloutSession = $this->entityManager->getRepository(CalloutSession::class)->findCalloutSessionActive($sessionId);
        if (!$calloutSession) {
            return ['error' => true, 'reason' => 'NO_SESSION_ID_FOUND'];
        }
        return $this->setCalloutSessionOnHold($calloutSession, CalloutSession::$LATER, $this->translator->trans('callout.message.later', ['name' => $calloutSession->getUser()->getFormatedName($this->themeService->getApplicationProperties('laf_showNameFrontend'))]));
    }

    /**
     * @param CalloutSession $calloutSession
     * @param $state
     * @param $message
     * @return array
     * This function is a generic function to set a calloutoutsession into the on hold status.
     * In this status the caller system is not able to do a ringing or a dial.
     *
     */
    public function setCalloutSessionOnHold(CalloutSession $calloutSession, $state, $message)
    {
        if ($calloutSession->getState() >= CalloutSession::$ON_HOLD || $calloutSession->getState() < CalloutSession::$DIALED) {
            return ['error' => true, 'reason' => 'SESSION_NOT_IN_CORRECT_STATE'];
        }
        $calloutSession->setState($state);
        $this->entityManager->persist($calloutSession);
        $this->entityManager->flush();
        $this->sendMessage($calloutSession->getRoom(), $message);
        $this->toModeratorWebsocketService->refreshLobbyByRoom($calloutSession->getRoom());
        $sipRaumnummer = $calloutSession->getRoom()->getCallerRoom();
        $pin = $this->entityManager->getRepository(CallerId::class)->findOneBy(['room' => $calloutSession->getRoom(), 'user' => $calloutSession->getUser()]);

        return [
            'status' => 'ON_HOLD',
            'pin' => $pin->getCallerId(),
            'room_number' => $sipRaumnummer->getCallerId(),
            'links' => [
                'back' => $this->urlGenerator->generate('callout_api_back', ['calloutSessionId' => $calloutSession->getUid()])
            ]
        ];
    }


    /**
     * @param Rooms $room
     * @param $message
     * @return void
     * This function is a generic function to send a message to the lobbymoderators.
     * The message is send via websocket
     */
    public function sendMessage(Rooms $room, $message)
    {
        $topic = 'lobby_moderator/' . $room->getUidReal();
        $this->directSendService->sendSnackbar($topic, $message, 'info',2000);
    }
}
