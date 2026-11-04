<?php

namespace App\Service\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class KeycloakService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function getUSer(string $email, ?string $keycloakId = null): ?User
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
