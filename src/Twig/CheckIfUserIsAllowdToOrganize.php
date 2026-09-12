<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Entity\User;
use App\UtilsHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CheckIfUserIsAllowdToOrganize extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('isAllowedToOrganize', [$this, 'isAllowedToOrganize'])
        ];
    }
    public function isAllowedToOrganize(Rooms $rooms, ?User $user)
    {
        return UtilsHelper::isAllowedToOrganizeRoom($user, $rooms);
    }
}
