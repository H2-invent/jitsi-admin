<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Service\PexelService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ImagePexels extends AbstractExtension
{
    private $pexelsService;
    public function __construct(PexelService $pexelService)
    {
        $this->pexelsService = $pexelService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('pexelsImage', [$this, 'pexelsImage']),
        ];
    }
    public function pexelsImage()
    {

        return $this->pexelsService->getImageFromPexels();
    }
}
