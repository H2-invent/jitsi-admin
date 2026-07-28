<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\MyUser;
use App\Entity\User;
use App\Service\MessageService;
use App\Service\Theme\ThemeService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use function GuzzleHttp\Psr7\str;

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
