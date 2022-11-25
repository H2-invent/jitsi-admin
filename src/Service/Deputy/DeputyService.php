<?php

namespace App\Service\Deputy;

use App\Entity\User;
use App\Service\Lobby\DirectSendService;
use App\Service\Lobby\ToModeratorWebsocketService;
use Doctrine\ORM\EntityManagerInterface;

class DeputyService
{
    static $IS_DEPUTY = 1;
    static $IS_NOT_DEPUTY = 2;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DirectSendService $directSendService
    )
    {
    }

    public function toggleDeputy(User $manager, User $deputy): int
    {
        if ($manager->getDeputy()->contains($deputy)) {
            return $this->removeDeputy($manager, $deputy);
        } else {
            return $this->setDeputy($manager, $deputy);
        }

    }

    public function setDeputy(User $manager, User $deputy): int
    {
        $manager->addDeputy($deputy);
        $this->entityManager->persist($manager);
        $this->entityManager->flush();
        $this->directSendService->sendRefreshDashboardToUser($deputy);
        return self::$IS_DEPUTY;
    }

    public function removeDeputy(User $manager, User $deputy):int
    {
        $manager->removeDeputy($deputy);
        $this->entityManager->persist($manager);
        $this->entityManager->flush();
        $this->directSendService->sendRefreshDashboardToUser($deputy);
        return self::$IS_NOT_DEPUTY;
    }
}