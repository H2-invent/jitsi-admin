<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\User;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Time extends AbstractExtension
{
    public function getFunctions(): array
    {

        return [
            new TwigFunction('getTime', [$this, 'getTime']),
        ];
    }

    public function getTime(User $user): \DateTimeImmutable
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone($user->getTimeZone()));
        return $now;
    }
}
