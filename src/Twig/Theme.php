<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Service\Theme\ThemeService;

class Theme
{
    public function __construct(private readonly ThemeService $themeService)
    {
    }

    /**
     * @return array<string, mixed>|false
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'getThemeProperties')]
    public function getThemeProperties(?Rooms $rooms = null): array|bool
    {
        return $this->themeService->getTheme($rooms);
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'getApplicationProperties')]
    public function getApplicationProperties(string $input): mixed
    {
        return $this->themeService->getApplicationProperties($input);
    }
}
