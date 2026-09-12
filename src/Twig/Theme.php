<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Service\Theme\ThemeService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Theme extends AbstractExtension
{
    private $themeService;
    public function __construct(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    public function getFunctions(): array
    {

        return [
            new TwigFunction('getThemeProperties', [$this, 'getThemeProperties']),
            new TwigFunction('getApplicationProperties', [$this, 'getApplicationProperties']),
        ];
    }

    public function getThemeProperties(?Rooms $rooms = null)
    {
        return $this->themeService->getTheme($rooms);
    }

    public function getApplicationProperties($input)
    {
        return $this->themeService->getApplicationProperties($input);
    }
}
