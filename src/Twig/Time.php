<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\User;
use App\Service\Theme\ThemeService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Time extends AbstractExtension
{
    private $themeService;
    public function __construct(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    public function getFunctions(): array
    {

        return [
            new TwigFunction('getTime', [$this, 'getTime']),
        ];
    }

    public function getTime(User $user)
    {
        $now = new \DateTime('now', new \DateTimeZone($user->getTimeZone()));
        return $now;
    }
}
