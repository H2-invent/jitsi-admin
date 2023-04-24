<?php

namespace App\Service\Callout;

use App\Entity\CallerId;
use App\Entity\CalloutSession;
use App\Entity\Rooms;
use App\Service\Lobby\ToModeratorWebsocketService;
use App\Service\ThemeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CallOutSessionAPIDialService
{
    public function __construct(
        private EntityManagerInterface      $entityManager,
        private UrlGeneratorInterface       $urlGenerator,
        private CalloutService              $calloutService,
        private ToModeratorWebsocketService $toModeratorWebsocketService,
    )
    {
    }

    /**
     * @param $sessionId
     * @return array
     * A session is dialed.
     * Every Session has to go through this session
     * Every Session has to be dialed after it is shown in the pool
     */
    public function dialSession($sessionId): array
    {
        $calloutSession = $this->entityManager->getRepository(CalloutSession::class)->findOneBy(array('uid' => $sessionId));
        if (!$calloutSession) {
            return array('error' => true, 'reason' => 'NO_SESSION_ID_FOUND');
        }
        if ($calloutSession->getState() >= CalloutSession::$ON_HOLD){
            return array('error' => true, 'reason' => 'SESSION_NOT_IN_CORRECT_STATE');
        }

        $pin = $this->entityManager->getRepository(CallerId::class)->findOneBy(array('room' => $calloutSession->getRoom(), 'user' => $calloutSession->getUser()));
        if ($calloutSession->getState() < CalloutSession::$DIALED){
            $calloutSession->setState(CalloutSession::$DIALED);
            $this->entityManager->persist($calloutSession);
            $this->entityManager->flush();
        }

        $this->toModeratorWebsocketService->refreshLobbyByRoom($calloutSession->getRoom());
        $res = array(
            'status' => 'OK',
            'links' => $this->generateLinkList(calloutSession: $calloutSession,pin: $pin),
        );
        return $res;

    }

    /**
     * @param string $sessionId
     * @return array
     * A session can be set in ringing state.
     * A ringing State is show with a different symbol in the frontend.
     * A Session has to be in DIaling state to be moved to the ringing state
     */
    public function ringing(string $sessionId):array{
        $calloutSession = $this->entityManager->getRepository(CalloutSession::class)->findOneBy(array('uid' => $sessionId));
        if (!$calloutSession) {
            return array('error' => true, 'reason' => 'NO_SESSION_ID_FOUND');
        }
        if ($calloutSession->getState() >= CalloutSession::$ON_HOLD || $calloutSession->getState() < CalloutSession::$DIALED){
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
            'links' => $this->generateLinkList(calloutSession: $calloutSession,pin: $pin),
        );

    }

    /**
     * @param CalloutSession $calloutSession
     * @param CallerId $pin
     * @return array
     * This function genetrates the link list
     * The Link list return the link for:
     * refuse, ringing, timeout, error, unreachable, later, dial, occupied,accept
     * the accept array retunrs the dial in information (pin and caller id) which is necessary to dialin in a meeting via phone
     */
    public function generateLinkList(CalloutSession $calloutSession, CallerId $pin):array{


        return array(
            'accept' => $this->urlGenerator->generate('caller_pin',
            array(
                'roomId' => $calloutSession->getRoom()->getCallerRoom()->getCallerId(),
                'caller_id' => $this->calloutService->getCallerIdForUser($calloutSession->getUser()),
                'pin' => $pin->getCallerId())
        ),
                'refuse' => $this->urlGenerator->generate('callout_api_refuse', array('calloutSessionId' => $calloutSession->getUid())),
                'ringing' => $this->urlGenerator->generate('callout_api_ringing', array('calloutSessionId' => $calloutSession->getUid())),
                'timeout' => $this->urlGenerator->generate('callout_api_timeout', array('calloutSessionId' => $calloutSession->getUid())),
                'error' => $this->urlGenerator->generate('callout_api_error', array('calloutSessionId' => $calloutSession->getUid())),
                'unreachable' => $this->urlGenerator->generate('callout_api_unreachable', array('calloutSessionId' => $calloutSession->getUid())),
                'later' => $this->urlGenerator->generate('callout_api_later', array('calloutSessionId' => $calloutSession->getUid())),
                'dial' => $this->urlGenerator->generate('callout_api_dial', array('calloutSessionId' => $calloutSession->getUid())),
                'occupied' => $this->urlGenerator->generate('callout_api_occupied', array('calloutSessionId' => $calloutSession->getUid())),
        );
    }

    /**
     * @param string $sessionId
     * @return array
     * This FUnction resets a session from on hold back to dial State.
     * This is necessary when a called user wants to revert his decision from f.eg. klicking want to join later but then he wants to join now
     */
    public function backSession(string $sessionId):array{
        $calloutSession = $this->entityManager->getRepository(CalloutSession::class)->findOneBy(array('uid' => $sessionId));
        if (!$calloutSession) {
            return array('error' => true, 'reason' => 'NO_SESSION_ID_FOUND');
        }
        if ($calloutSession->getState() < CalloutSession::$ON_HOLD){//Wenn die Session
            return array('error' => true, 'reason' => 'SESSION_NOT_IN_CORRECT_STATE');
        }
        $calloutSession->setState(CalloutSession::$DIALED);
        $this->entityManager->persist($calloutSession);
        $this->entityManager->flush();
        $this->toModeratorWebsocketService->refreshLobbyByRoom($calloutSession->getRoom());
        $pin = $this->entityManager->getRepository(CallerId::class)->findOneBy(array('room' => $calloutSession->getRoom(), 'user' => $calloutSession->getUser()));
        return array(
            'status' => CalloutSession::$STATE[$calloutSession->getState()],
            'links' => $this->generateLinkList(calloutSession: $calloutSession,pin: $pin),
        );

    }

}