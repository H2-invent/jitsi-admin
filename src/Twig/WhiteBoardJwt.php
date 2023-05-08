<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\MyUser;
use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Service\LicenseService;
use App\Service\MessageService;
use App\Service\ThemeService;
use App\Service\Websocket\WebsocketJwtService;
use App\Service\Whiteboard\WhiteboardJwtService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
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
