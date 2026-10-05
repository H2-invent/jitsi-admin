<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Entity\User;
use App\UtilsHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CheckIfUserIsAllowdToOrganize
{
    #[\Twig\Attribute\AsTwigFunction(name: 'isAllowedToOrganize')]
    public function isAllowedToOrganize(Rooms $rooms, ?User $user): bool
    {
        return UtilsHelper::isAllowedToOrganizeRoom($user, $rooms);
    }
}
