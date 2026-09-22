<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\User;
use App\Service\Websocket\WebsocketJwtService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class WebsocketJwt extends AbstractExtension
{
    public function __construct(
        private WebsocketJwtService   $websocketJwtService,
        private ParameterBagInterface $parameterBag
    )
    {
    }

    public function getFunctions(): array
    {

        return [
            new TwigFunction('getJwtforWebsocket', [$this, 'getJwtforWebsocket']),
            new TwigFunction('getUrlforWebsocket', [$this, 'getUrlforWebsocket']),

        ];
    }

    /**
     * @param array<int, string> $rooms
     * @return string
     */
    public function getJwtforWebsocket(array $rooms, ?User $user): string
    {
        return $this->websocketJwtService->createJwt($rooms, $user);
    }

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
