<?php

namespace App\Service\caller;

use App\Entity\CallerSession;
use App\Service\Lobby\ToModeratorWebsocketService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CallerLeftService
{
    private EntityManagerInterface $em;
    private LoggerInterface $loggger;
    private CallerSessionService $sessionService;

    public function __construct(CallerSessionService $callerSessionService, LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        $this->loggger = $logger;
        $this->sessionService = $callerSessionService;
    }

    /**
     * @param string $sessionId
     * @return bool
     */
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
