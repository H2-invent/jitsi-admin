<?php

namespace App\Service\caller;

use App\Entity\CallerSession;
use App\Service\Lobby\ToModeratorWebsocketService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CallerLeftService
{
    private $em;
    private $loggger;
    private $sessionService;
    private ToModeratorWebsocketService $moderatorWebsocketService;

    public function __construct(ToModeratorWebsocketService $toModeratorWebsocketService, CallerSessionService $callerSessionService, LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        $this->loggger = $logger;
        $this->sessionService = $callerSessionService;
        $this->moderatorWebsocketService = $toModeratorWebsocketService;
    }

    public function callerLeft($sessionId)
    {
        $session = $this->em->getRepository(CallerSession::class)->findOneBy(['sessionId' => $sessionId]);
        if (!$session) {
            $this->loggger->error('Session not found', ['sessionId' => $sessionId]);
            return true;
        }
        $this->loggger->debug('The Session is cleaned up', ['sessionId' => $sessionId]);

        $this->sessionService->cleanUpSession($session);
        return false;
    }
}
