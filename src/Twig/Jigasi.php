<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\MyUser;
use App\Entity\Rooms;
use App\Service\Jigasi\JigasiService;
use App\Service\MessageService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use function GuzzleHttp\Psr7\str;

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
