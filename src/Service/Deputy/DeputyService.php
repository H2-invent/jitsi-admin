<?php

namespace App\Service\Deputy;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class DeputyService
{
    static $IS_DEPUTY = 1;
    static $IS_NOT_DEPUTY = 2;

    public function __construct(
        private EntityManagerInterface $entityManager
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
        return self::$IS_DEPUTY;
    }

    public function removeDeputy(User $manager, User $deputy):int
    {
        $manager->removeDeputy($deputy);
        $this->entityManager->persist($manager);
        $this->entityManager->flush();
        return self::$IS_NOT_DEPUTY;
    }
}