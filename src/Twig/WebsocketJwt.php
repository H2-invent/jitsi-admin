<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\User;
use App\Service\Websocket\WebsocketJwtService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class WebsocketJwt
{
    public function __construct(
        private readonly WebsocketJwtService   $websocketJwtService,
        private readonly ParameterBagInterface $parameterBag
    ) {
    }

    /**
     * @param array<int, string> $rooms
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'getJwtforWebsocket')]
    public function getJwtforWebsocket(array $rooms, ?User $user): string
    {
        return $this->websocketJwtService->createJwt($rooms, $user);
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'getUrlforWebsocket')]
    public function getUrlforWebsocket(): string
    {
        /** @var string $path */
        $path = $this->parameterBag->get('MERCURE_PUBLIC_URL');
        if (str_contains($path, 'https')) {
            $path = str_replace('https', 'wss', $path);
        } else {
            $path = str_replace('http', 'ws', $path);
        }
        return $path;
    }
}
