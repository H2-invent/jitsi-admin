<?php

namespace App\Service\caller;

use App\Entity\CallerSession;
use App\Service\webhook\RoomStatusFrontendService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CallerSessionService
{
    private $em;
    private $roomStatus;
    private $loggger;

    public function __construct(LoggerInterface $logger, RoomStatusFrontendService $roomStatusFrontendService, EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        $this->roomStatus = $roomStatusFrontendService;
        $this->loggger = $logger;
    }

    public function getSession($sessionId)
    {
        $session = $this->em->getRepository(CallerSession::class)->findOneBy(array('sessionId' => $sessionId));
        if (!$session) {
            return array(
                'status' => 'HANGUP',
                'reason' => 'WRONG_SESSION'
            );
        }
        if (!$session->getLobbyWaitingUser()) {
            $this->cleanUpSession($session);
            return array(
                'status' => 'HANGUP',
                'reason' => 'DECLINED',
            );
        }
        $participants = sizeof($this->roomStatus->numberOfOccupants($session->getLobbyWaitingUser()->getRoom()));
        $closed = $this->roomStatus->isRoomClosed($session->getLobbyWaitingUser()->getRoom());
        $started = $this->roomStatus->isRoomCreated($session->getLobbyWaitingUser()->getRoom());
        $authOk = $session->getAuthOk();


        if ($closed == false && $started == false && $authOk == false) {
            return array(
                'status' => 'WAITING',
                'reason' => 'NOT_ACCEPTED',
                'number_of_participants' => $participants,
                'status_of_meeting' => 'NOT_STARTED'
            );
        }
        if ($authOk == false && $started == true) {
            return array(
                'status' => 'WAITING',
                'reason' => 'NOT_ACCEPTED',
                'number_of_participants' => $participants,
                'status_of_meeting' => 'STARTED'
            );
        }

        if ($closed == true) {
            $this->cleanUpSession($session);
            return array(
                'status' => 'HANGUP',
                'reason' => 'MEETING_HAS_FINISHED'
            );
        }


        if ($authOk) {
            return array(
                'status' => 'ACCEPTED',
                'reason' => 'ACCEPTED_BY_MODERATOR',
                'number_of_participants' => $participants,
                'status_of_meeting' => 'NOT_STARTED'
            );
        }
        $this->cleanUpSession($session);
        return array(
            'status' => 'HANGUP',
            'reason' => 'ERROR',
        );
    }

    public function cleanUpSession(CallerSession $callerSession)
    {
        try {
            if ($callerSession->getLobbyWaitingUser()) {
                $this->em->remove($callerSession->getLobbyWaitingUser());
            }

            $this->em->remove($callerSession);
            $this->em->flush();
        } catch (\Exception $exception) {
            $this->loggger->error($exception->getMessage());
            return false;
        }
        return true;
    }
}