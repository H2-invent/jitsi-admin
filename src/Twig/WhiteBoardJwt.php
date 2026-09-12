<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Service\Whiteboard\WhiteboardJwtService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class WhiteBoardJwt extends AbstractExtension
{
    public function __construct(
        private WhiteboardJwtService  $whiteboardJwtService,
    )
    {
    }

    public function getFunctions(): array
    {

        return [
            new TwigFunction('getJwtforWhiteboard', [$this, 'getJwtforWhiteboard']),

        ];
    }

    public function getJwtforWhiteboard(Rooms $room, $isModerator = false)
    {
        return $this->whiteboardJwtService->createJwt($room, $isModerator);
    }
}
