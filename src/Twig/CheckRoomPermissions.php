<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CheckRoomPermissions
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'roomPermissions')]
    public function roomPermissions(User $user, Rooms $rooms): ?RoomsUser
    {
        $permissions = $this->em->getRepository(RoomsUser::class)->findOneBy(['user' => $user, 'room' => $rooms]);
        if (!$permissions) {
            $permissions = new RoomsUser();
        }
        return $permissions;
    }
}
