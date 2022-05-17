<?php

namespace App\Service\caller;

use App\Entity\CallerSession;
use App\Entity\LobbyWaitungUser;
use App\Service\Lobby\ToModeratorWebsocketService;
use App\Service\RoomService;
use App\Service\webhook\RoomStatusFrontendService;
use Doctrine\ORM\EntityManagerInterface;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CallerSessionService
{
    private $em;
    private $roomStatus;
    private $loggger;
    private $toModerator;
    private $roomService;
    private UrlGeneratorInterface $urlGen;
    private RequestStack $requestStack;
    public function __construct(RequestStack $requestStack, UrlGeneratorInterface $urlGenerator, RoomService $roomService, ToModeratorWebsocketService $toModeratorWebsocketService, LoggerInterface $logger, RoomStatusFrontendService $roomStatusFrontendService, EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        $this->roomStatus = $roomStatusFrontendService;
        $this->loggger = $logger;
        $this->toModerator = $toModeratorWebsocketService;
        $this->roomService = $roomService;
        $this->urlGen = $urlGenerator;
        $this->requestStack = $requestStack;
    }

    public function getSessionStatus($sessionId)
    {
        $this->loggger->debug('Start with Session', array('sessionId' => $sessionId));
        $session = $this->em->getRepository(CallerSession::class)->findOneBy(array('sessionId' => $sessionId));
        if (!$session) {
            $this->loggger->debug('No Session found', array('sessionId' => $sessionId));
            if ($this->requestStack->getCurrentRequest()){
                $this->loggger->emergency('Wrong Session-ID', array('sessionId' => $sessionId,'ip'=>$this->requestStack->getCurrentRequest()->getClientIp()));
            }else{
                $this->loggger->debug('We are in a Test');
            }
            return array(
                'status' => 'HANGUP',
                'reason' => 'WRONG_SESSION',
            );
        }
        $participants = sizeof($this->roomStatus->numberOfOccupants($session->getCaller()->getRoom()));
        $closed = $this->roomStatus->isRoomClosed($session->getCaller()->getRoom());
        $started = $this->roomStatus->isRoomCreated($session->getCaller()->getRoom());
        $authOk = $session->getAuthOk();

        if ($session->getForceFinish()) {
            $this->loggger->debug('The user is called to hangup. The Moderator has ended the meeting for all participants', array('sessionId' => $sessionId, 'callerId' => $session->getCallerId()));
            $this->cleanUpSession($session);
            return array(
                'status' => 'HANGUP',
                'reason' => 'MEETING_HAS_FINISHED',
                'links' => array(
                    'left' => $this->urlGen->generate('caller_left', array('session_id' => $session->getSessionId()))
                )
            );
        }

        if ($authOk) {
            $this->loggger->debug('The user is accepted and is allowed to enter the room', array('sessionId' => $sessionId, 'callerId' => $session->getCallerId(), 'user' => $session->getCaller()->getUser()->getId()));
            return array(
                'status' => 'ACCEPTED',
                'reason' => 'ACCEPTED_BY_MODERATOR',
                'number_of_participants' => $participants,
                'status_of_meeting' => 'STARTED',
                'jwt' => $this->roomService->generateJwt($session->getCaller()->getRoom(), $session->getCaller()->getUser(), $session->getShowName()),
                'links' => array(
                    'session' => $this->urlGen->generate('caller_session', array('session_id' => $session->getSessionId())),
                    'left' => $this->urlGen->generate('caller_left', array('session_id' => $session->getSessionId()))
                )
            );
        }


        if (!$session->getLobbyWaitingUser() && $authOk === false) {
            $this->loggger->debug('The Session was declined by the lobbymoderator', array('sessionId' => $sessionId));
            $this->cleanUpSession($session);
            return array(
                'status' => 'HANGUP',
                'reason' => 'DECLINED',
                'links' => array(
                )
            );
        }


        if ($closed == false && $started == false && $authOk == false) {
            $this->loggger->debug('The Room is not startd and the User hast to wait. The user is not accepted', array('sessionId' => $sessionId, 'callerId' => $session->getCallerId(), 'name' => $session->getLobbyWaitingUser()->getShowName()));
            return array(
                'status' => 'WAITING',
                'reason' => 'NOT_ACCEPTED',
                'number_of_participants' => $participants,
                'status_of_meeting' => 'NOT_STARTED',
                'links' => array(
                    'session' => $this->urlGen->generate('caller_session', array('session_id' => $session->getSessionId())),
                    'left' => $this->urlGen->generate('caller_left', array('session_id' => $session->getSessionId()))
                )
            );
        }

        if ($authOk == false && $started == true) {
            $this->loggger->debug('The Room is  startd and the User hast to wait. The user is not accepted', array('sessionId' => $sessionId, 'callerId' => $session->getCallerId(), 'name' => $session->getLobbyWaitingUser()->getShowName()));

            return array(
                'status' => 'WAITING',
                'reason' => 'NOT_ACCEPTED',
                'number_of_participants' => $participants,
                'status_of_meeting' => 'STARTED',
                'links' => array(
                    'session' => $this->urlGen->generate('caller_session', array('session_id' => $session->getSessionId())),
                    'left' => $this->urlGen->generate('caller_left', array('session_id' => $session->getSessionId()))
                )
            );
        }

        if ($closed == true) {
            $this->loggger->debug('The user is called to hangup. The Meeting has finished while he was waiting', array('sessionId' => $sessionId, 'callerId' => $session->getCallerId(), 'name' => $session->getLobbyWaitingUser()->getShowName()));

            $this->cleanUpSession($session);
            return array(
                'status' => 'HANGUP',
                'reason' => 'MEETING_HAS_FINISHED',
                'links' => array(
                    'left' => $this->urlGen->generate('caller_left', array('session_id' => $session->getSessionId()))
                )
            );
        }


        $this->loggger->error('Error. an UNKNOWN state occured.', array('sessionId' => $sessionId, 'callerId' => $session->getCallerId(), 'name' => $session->getLobbyWaitingUser()->getShowName()));

        $this->cleanUpSession($session);
        return array(
            'status' => 'HANGUP',
            'reason' => 'ERROR',
            'links' => array(
                'left' => $this->urlGen->generate('caller_left', array('session_id' => $session->getSessionId()))
            )
        );
    }

    public function cleanUpSession(CallerSession $callerSession)
    {
        $this->loggger->debug('We start to destroy the caller session', array('sessionID' => $callerSession->getSessionId()));
        try {
            $lobbyWaitungUser = $callerSession->getLobbyWaitingUser();
            if ($lobbyWaitungUser) {
                $this->loggger->debug('There is a Lobbyuser. we send a refres to the lobbymoderator', array('room' => $lobbyWaitungUser->getRoom()->getId()));
                $this->toModerator->refreshLobby($lobbyWaitungUser);
                $this->toModerator->participantLeftLobby($lobbyWaitungUser);
                $this->em->remove($lobbyWaitungUser);
            }

            $this->loggger->debug('The Callersession is destroyed', array('room' => $callerSession->getSessionId()));
            $callerId = $callerSession->getCaller();
            $callerId->setCallerSession(null);
            $this->em->remove($callerSession);
            $this->em->flush();


        } catch (\Exception $exception) {
            $this->loggger->error($exception->getMessage());
            return false;
        }
        $this->loggger->debug('The Callersession is sucessfully destroyed', array('room' => $callerSession->getSessionId()));
        return true;
    }

    public function acceptCallerUser(LobbyWaitungUser $lobbyWaitungUser)
    {
        if ($lobbyWaitungUser->getCallerSession()) {
            $caller = $lobbyWaitungUser->getCallerSession();
            $caller->setLobbyWaitingUser(null);
            $caller->setAuthOk(true);
            $this->em->persist($caller);
            $this->em->flush();
            return true;
        }
        return false;
    }
}