<?php

namespace App\Service\Caller;

use App\Entity\CallerSession;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CallerLeftService
{
    public function __construct(private readonly CallerSessionService   $sessionService,
                                private readonly LoggerInterface        $loggger,
                                private readonly EntityManagerInterface $em
    ) {
    }

    public function callerLeft(string $sessionId): bool
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
