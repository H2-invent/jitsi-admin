<?php

namespace App\Service\caller;

use App\Entity\CallerSession;
use App\Service\webhook\RoomStatusFrontendService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CallerLeftService
{
    private $em;
    private $loggger;
    private $sessionService;
    public function __construct(CallerSessionService $callerSessionService, LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        $this->loggger = $logger;
        $this->sessionService = $callerSessionService;
    }
    public function callerLeft($sessionId){
        $session = $this->em->getRepository(CallerSession::class)->findOneBy(array('sessionId'=>$sessionId));
        if (!$session){
            $this->loggger->error('Session not found',array('sessionId'=>$sessionId));
            return true;
        }
        $this->loggger->debug('The Session is cleaned up', array('sessionId'=>$sessionId));

        $this->sessionService->cleanUpSession($session);
        return false;
    }
}