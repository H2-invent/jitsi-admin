<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\MyUser;
use App\Entity\Rooms;
use App\Entity\User;
use App\Service\MessageService;
use App\UtilsHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use function GuzzleHttp\Psr7\str;

class CheckIfUserIsAllowdToOrganize extends AbstractExtension
{
    public function getFunctions()
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
