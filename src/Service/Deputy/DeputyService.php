<?php

namespace App\Service\Deputy;

use App\Entity\Deputy;
use App\Entity\User;
use App\Service\Lobby\DirectSendService;
use Doctrine\ORM\EntityManagerInterface;

class DeputyService
{
    static $IS_DEPUTY = 1;
    static $IS_NOT_DEPUTY = 2;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DirectSendService      $directSendService
    )
    {
    }

    public function toggleDeputy(User $manager, User $deputy): int
    {
        $dep = $this->entityManager->getRepository(Deputy::class)->findOneBy(['deputy' => $deputy, 'manager' => $manager]);
        if ($dep) {
            return $this->removeDeputy($manager, $deputy);
        } else {
            return $this->setDeputy($manager, $deputy);
        }
    }

    public function setDeputy(User $manager, User $deputy): int
    {
        $dep = $this->entityManager->getRepository(Deputy::class)->findOneBy(['deputy' => $deputy, 'manager' => $manager]);
        if (!$dep) {
            $dep = new Deputy();
            $dep->setManager($manager);
            $dep->setDeputy($deputy);
            $dep->setCreatedAt(new \DateTime());
            $dep->setIsFromLdap(false);
            $this->entityManager->persist($dep);
            $this->entityManager->flush();
            $this->directSendService->sendRefreshDashboardToUser($deputy);
        }

        return self::$IS_DEPUTY;
    }

    public function removeDeputy(User $manager, User $deputy): int
    {
        $dep = $this->entityManager->getRepository(Deputy::class)->findOneBy(['deputy' => $deputy, 'manager' => $manager]);
        if ($dep) {
            $this->entityManager->remove($dep);
            $this->entityManager->flush();
            $this->directSendService->sendRefreshDashboardToUser($deputy);
        }


        return self::$IS_NOT_DEPUTY;
    }
}
