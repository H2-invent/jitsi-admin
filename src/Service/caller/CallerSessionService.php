<?php

namespace App\Service\caller;

use App\Entity\CallerSession;
use App\Entity\LobbyWaitungUser;
use App\Service\FormatName;
use App\Service\Lobby\ToModeratorWebsocketService;
use App\Service\RoomService;
use App\Service\ThemeService;
use App\Service\webhook\RoomStatusFrontendService;
use Doctrine\ORM\EntityManagerInterface;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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

    private int $particpants;

    public function __construct(
        RequestStack                $requestStack,
        UrlGeneratorInterface       $urlGenerator,
        RoomService                 $roomService,
        ToModeratorWebsocketService $toModeratorWebsocketService,
        LoggerInterface             $logger,
        RoomStatusFrontendService   $roomStatusFrontendService,
        EntityManagerInterface      $entityManager,
        private FormatName          $formatName,
        private ThemeService        $themeService)
    {
        $this->em = $entityManager;
        $this->roomStatus = $roomStatusFrontendService;
        $this->loggger = $logger;
        $this->toModerator = $toModeratorWebsocketService;
        $this->roomService = $roomService;
        $this->urlGen = $urlGenerator;
        $this->requestStack = $requestStack;
    }

    public
    function getSessionStatus($sessionId)
    {
        $this->loggger->debug('Start with Session', array('sessionId' => $sessionId));
        $session = $this->em->getRepository(CallerSession::class)->findOneBy(array('sessionId' => $sessionId));
        if (!$session) {
            $this->loggger->debug('No Session found', array('sessionId' => $sessionId));
            if ($this->requestStack->getCurrentRequest()) {
                $this->loggger->emergency('Wrong Session-ID', array('sessionId' => $sessionId, 'ip' => $this->requestStack->getCurrentRequest()->getClientIp()));
            } else {
                $this->loggger->debug('We are in a Test');
            }
            return array(
                'status' => 'HANGUP',
                'reason' => 'WRONG_SESSION',
            );
        }
        $this->particpants = sizeof($this->roomStatus->numberOfOccupants($session->getCaller()->getRoom()));
        $closed = $this->roomStatus->isRoomClosed($session->getCaller()->getRoom());
        $started = $this->roomStatus->isRoomCreated($session->getCaller()->getRoom());
        $authOk = $session->getAuthOk();

        if ($session->getForceFinish()) {
            $this->loggger->debug('The user is called to hangup. The Moderator has ended the meeting for all participants', array('sessionId' => $sessionId, 'callerId' => $session->getCallerId()));
            $this->cleanUpSession($session);
            return $this->sessionMeetingFinished(session: $session);
        }

        if ($authOk || (!$session->getCaller()->getRoom()->getLobby() && $started)) {
            $this->loggger->debug('The user is accepted and is allowed to enter the room', array('sessionId' => $sessionId, 'callerId' => $session->getCallerId(), 'user' => $session->getCaller()->getUser()->getId()));
            return $this->sessionAccepted(session: $session);

        }

        if (!$session->getLobbyWaitingUser() && $authOk === false) {
            $this->loggger->debug('The Session was declined by the lobbymoderator', array('sessionId' => $sessionId));
            $this->cleanUpSession($session);
            return $this->sessionDeclined(session: $session);

        }


        if ($closed == false && $started == false && $authOk == false) {
            $this->loggger->debug('The Room is not startd and the User hast to wait. The user is not accepted', array('sessionId' => $sessionId, 'callerId' => $session->getCallerId(), 'name' => $session->getLobbyWaitingUser()->getShowName()));
            return $this->sessionWaiting(session: $session, started: false);

        }

        if ($authOk == false && $started == true) {
            $this->loggger->debug('The Room is  startd and the User hast to wait. The user is not accepted', array('sessionId' => $sessionId, 'callerId' => $session->getCallerId(), 'name' => $session->getLobbyWaitingUser()->getShowName()));
            return $this->sessionWaiting(session: $session, started: true);

        }

        if ($closed == true) {
            $this->loggger->debug('The user is called to hangup. The Meeting has finished while he was waiting', array('sessionId' => $sessionId, 'callerId' => $session->getCallerId(), 'name' => $session->getLobbyWaitingUser()->getShowName()));

            $this->cleanUpSession($session);
            return $this->sessionMeetingFinished(session: $session);

        }


        $this->loggger->error('Error. an UNKNOWN state occured.', array('sessionId' => $sessionId, 'callerId' => $session->getCallerId(), 'name' => $session->getLobbyWaitingUser()->getShowName()));

        $this->cleanUpSession($session);
        return $this->sessionError(session: $session);
    }

    public
    function cleanUpSession(CallerSession $callerSession)
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

    public
    function acceptCallerUser(LobbyWaitungUser $lobbyWaitungUser)
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

    private function sessionMeetingFinished(CallerSession $session): array
    {
        return array(
            'status' => 'HANGUP',
            'reason' => 'MEETING_HAS_FINISHED',
            'message'=>$this->createMessageElement($session),
            'links' => array(
                'left' => $this->urlGen->generate('caller_left', array('session_id' => $session->getSessionId()))
            )
        );
    }

    private function sessionAccepted(CallerSession $session): array
    {
        return array(
            'status' => 'ACCEPTED',
            'reason' => 'ACCEPTED_BY_MODERATOR',
            'number_of_participants' => $this->particpants,
            'status_of_meeting' => 'STARTED',
            'message'=>$this->createMessageElement($session),
            'room_name' => $session->getCaller()->getRoom()->getUid(),
            'displayname' => $this->formatName->formatName($this->themeService->getApplicationProperties('laf_showNameInConference'), $session->getCaller()->getUser()),
            'jwt' => $this->roomService->generateJwt($session->getCaller()->getRoom(), $session->getCaller()->getUser(), $session->getShowName()),
            'links' => array(
                'session' => $this->urlGen->generate('caller_session', array('session_id' => $session->getSessionId())),
                'left' => $this->urlGen->generate('caller_left', array('session_id' => $session->getSessionId()))
            )
        );
    }

    private function sessionDeclined(CallerSession $session): array
    {
        return array(
            'status' => 'HANGUP',
            'reason' => 'DECLINED',
            'message'=>$this->createMessageElement($session),
            'links' => array()
        );
    }

    private function sessionWaiting(CallerSession $session, bool $started): array
    {
        return array(
            'status' => 'WAITING',
            'reason' => 'NOT_ACCEPTED',
            'number_of_participants' => $this->particpants,
            'status_of_meeting' => $started ? 'STARTED' : 'NOT_STARTED',
            'message'=>$this->createMessageElement($session),
            'links' => array(
                'session' => $this->urlGen->generate('caller_session', array('session_id' => $session->getSessionId())),
                'left' => $this->urlGen->generate('caller_left', array('session_id' => $session->getSessionId()))
            )
        );
    }

    private function sessionError(CallerSession $session): array
    {
        return array(
            'status' => 'HANGUP',
            'reason' => 'ERROR',
            'message'=>$this->createMessageElement($session),
            'links' => array(
                'left' => $this->urlGen->generate('caller_left', array('session_id' => $session->getSessionId()))
            )
        );
    }
    public function createMessageElement(CallerSession $session){
        return $session->getMessageUid()?array('uid'=>$session->getMessageUid(),'message'=>$session->getMessageText()):array();
    }
}