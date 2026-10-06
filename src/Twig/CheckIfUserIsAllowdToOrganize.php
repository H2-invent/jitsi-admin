<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Entity\User;
use App\UtilsHelper;

class CheckIfUserIsAllowdToOrganize
{
    #[\Twig\Attribute\AsTwigFunction(name: 'isAllowedToOrganize')]
    public function isAllowedToOrganize(Rooms $rooms, ?User $user): bool
    {
        return UtilsHelper::isAllowedToOrganizeRoom($user, $rooms);
    }
}
