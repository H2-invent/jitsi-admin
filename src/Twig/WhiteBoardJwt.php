<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\MyUser;
use App\Entity\Rooms;
use App\Service\MessageService;
use App\Service\Whiteboard\WhiteboardJwtService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use function GuzzleHttp\Psr7\str;

class WhiteBoardJwt extends AbstractExtension
{
    public function __construct(
        private WhiteboardJwtService  $whiteboardJwtService,
        private ParameterBagInterface $parameterBag
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
