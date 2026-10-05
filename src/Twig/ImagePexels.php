<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Service\PexelService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ImagePexels
{
    public function __construct(private readonly PexelService $pexelsService)
    {
    }
    /**
     * @return array<string, mixed>|null
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'pexelsImage')]
    public function pexelsImage(): ?array
    {

        return $this->pexelsService->getImageFromPexels();
    }
}
