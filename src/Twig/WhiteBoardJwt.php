<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Service\Whiteboard\WhiteboardJwtService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class WhiteBoardJwt
{
    public function __construct(
        private readonly WhiteboardJwtService  $whiteboardJwtService,
    )
    {
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'getJwtforWhiteboard')]
    public function getJwtforWhiteboard(Rooms $room, bool $isModerator = false): string
    {
        return $this->whiteboardJwtService->createJwt($room, $isModerator);
    }
}
