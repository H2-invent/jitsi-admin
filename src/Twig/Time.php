<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\User;

class Time
{
    #[\Twig\Attribute\AsTwigFunction(name: 'getTime')]
    public function getTime(User $user): \DateTimeImmutable
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone($user->getTimeZone()));
        return $now;
    }
}
