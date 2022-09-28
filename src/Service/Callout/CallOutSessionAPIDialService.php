<?php

namespace App\Service\Callout;

use App\Entity\CallerId;
use App\Entity\CalloutSession;
use App\Service\ThemeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CallOutSessionAPIDialService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface  $urlGenerator,
        private CalloutService         $calloutService
    )
    {
    }

    public function dialSession($sessionId): array
    {
        $calloutSession = $this->entityManager->getRepository(CalloutSession::class)->findOneBy(array('uid' => $sessionId));
        if (!$calloutSession) {
            return array('error' => true, 'reason' => 'NO_SESSION_ID_FOUND');
        }
        $pin = $this->entityManager->getRepository(CallerId::class)->findOneBy(array('room' => $calloutSession->getRoom(), 'user' => $calloutSession->getUser()));
        $calloutSession->setState(CalloutSession::$DIALED);
        $this->entityManager->persist($calloutSession);
        $this->entityManager->flush();
        $res = array(
            'status' => 'OK',
            'links' => array(
                'accept' => $this->urlGenerator->generate('caller_pin',
                    array(
                        'roomId' => $calloutSession->getRoom()->getCallerRoom()->getCallerId(),
                        'caller_id' => $this->calloutService->getCallerIdForUser($calloutSession->getUser()),
                        'pin' => $pin->getCallerId())
                ),
                'refuse' => 'test',
                'timeout' => 'test',
                'error' => 'test',
                'later' => 'test',
                'dial' => 'test',
                'occupied' => 'test',
            )
        );
        return $res;

    }
}