<?php

namespace App\Service\Callout;

use App\Entity\CalloutSession;
use App\Entity\Rooms;
use App\Entity\User;
use App\Service\adhocmeeting\AdhocMeetingService;
use Doctrine\ORM\EntityManagerInterface;

class CalloutService
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AdhocMeetingService    $adhocMeetingService,
    )
    {
    }

    public function initCalloutSession(Rooms $rooms, User $user, User $inviter): CalloutSession
    {
        $callout = $this->createCallout($rooms, $user, $inviter);
        $this->adhocMeetingService->sendAddhocMeetingWebsocket($user, $inviter, $rooms);
        return $callout;
    }

    public function createCallout(Rooms $rooms, User $user, User $inviter): CalloutSession
    {
        $callout = $this->entityManager->getRepository(CalloutSession::class)->findOneBy(array('room' => $rooms, 'user' => $user));
        if ($callout) {
            return $callout;
        }

        $callout = new CalloutSession();
        $callout->setUser($user)
            ->setRoom($rooms)
            ->setCreatedAt(new \DateTime())
            ->setInvitedFrom($inviter);
        $this->entityManager->persist($callout);
        $this->entityManager->flush();
        return $callout;
    }

    public function checkCallout(Rooms $rooms, User $user): bool
    {
        $callout = $this->entityManager->getRepository(CalloutSession::class)->findOneBy(array('room' => $rooms, 'user' => $user));
        return (bool)$callout;
    }

}