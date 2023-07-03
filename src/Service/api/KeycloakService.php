<?php

namespace App\Service\api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class KeycloakService
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function getUSer($email, $keycloakId = null): ?User
    {
        $user = null;
        if ($keycloakId) {
            $user = $this->em->getRepository(User::class)->findOneBy(['keycloakId' => $keycloakId]);
            if ($user) {
                return $user;
            }
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        return $user;
    }
}
