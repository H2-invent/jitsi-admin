<?php

namespace App\Service\Callout;

use App\Entity\CalloutSession;
use App\Entity\Rooms;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CalloutServiceDialSuccessfull
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface        $logger,
    )
    {
    }


    /**
     * @param User $user
     * @param Rooms $rooms
     * @return bool
     * This function removes the calloutsession when a called user is successfully transformed into a callin user.
     */
    public function dialSuccessfull(User $user, Rooms $rooms): bool
    {
        $calloutRepo = $this->entityManager->getRepository(CalloutSession::class);
        $calloutSession = $calloutRepo->findOneBy(['room' => $rooms, 'user' => $user]);

        if ($calloutSession) {
            $calloutSession = $calloutRepo->findCalloutSessionActive($calloutSession->getUid());
            if ($calloutSession) {
                $this->entityManager->remove($calloutSession);
                $this->entityManager->flush();
                $this->logger->debug('The Calloutsession was destoyed Successfully');
                return true;
            }
            $this->logger->debug('There is no valid Callout Session which can be destroyed. The Calloutsession is not in the right state');
        } else {
            $this->logger->debug('There is no calloutsession with this user and room');
        }
        return false;
    }
}
