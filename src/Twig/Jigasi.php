<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Service\Jigasi\JigasiService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Jigasi
{
    public function __construct(private readonly JigasiService $jigasiService)
    {
    }

    /**
     * @return array<mixed>|null
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'getJigasiNumber')]
    public function getJigasiNumber(?Rooms $rooms = null): ?array
    {
        return $this->jigasiService->getNumber($rooms);
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'getJigasiPin')]
    public function getJigasiPin(?Rooms $rooms = null): ?string
    {
        return $this->jigasiService->getRoomPin($rooms);
    }
}
