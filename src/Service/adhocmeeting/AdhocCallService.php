<?php

namespace App\Service\adhocmeeting;

use App\Entity\CalloutSession;
use App\Entity\Rooms;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Tracks the outcome of a web ad-hoc call so the scheduled timeout can tell whether the callee
 * actually answered. Answering removes the CalloutSession, which makes the timeout a no-op.
 */
class AdhocCallService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function markAnswered(User $user, Rooms $room): bool
    {
        // Only rooms created ad-hoc can have a waiting web callout. Skipping scheduled,
        // persistent and repeater rooms keeps a normal conference join from querying
        // callout_session at all.
        if ($room->getScheduleMeeting() || $room->getPersistantRoom() || $room->getRepeater()) {
            return false;
        }

        $calloutSession = $this->entityManager->getRepository(CalloutSession::class)
            ->findOneBy(['room' => $room, 'user' => $user]);

        if ($calloutSession && CalloutSession::isWaitingState($calloutSession->getState())) {
            $this->entityManager->remove($calloutSession);
            $this->entityManager->flush();
            return true;
        }

        return false;
    }
}
