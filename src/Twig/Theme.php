<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Service\Theme\ThemeService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Theme extends AbstractExtension
{
    private ThemeService $themeService;
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

    /**
     * @return array<string, mixed>|false
     */
    public function getThemeProperties(?Rooms $rooms = null): array|bool
    {
        return $this->themeService->getTheme($rooms);
    }

    /**
     * @param string $input
     * @return mixed
     */
    public function getApplicationProperties(string $input): mixed
    {
        return $this->themeService->getApplicationProperties($input);
    }
}
