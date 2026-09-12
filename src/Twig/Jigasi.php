<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Service\Jigasi\JigasiService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Jigasi extends AbstractExtension
{
    public function __construct(private JigasiService $jigasiService)
    {
    }

    public function getFunctions(): array
    {

        return [
            new TwigFunction('getJigasiNumber', [$this, 'getJigasiNumber']),
            new TwigFunction('getJigasiPin', [$this, 'getJigasiPin']),
        ];
    }

    public function getJigasiNumber(?Rooms $rooms = null)
    {
        return $this->jigasiService->getNumber($rooms);
    }

    public function getJigasiPin(?Rooms $rooms = null)
    {
        return $this->jigasiService->getRoomPin($rooms);
    }
}
