<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\MyUser;
use App\Entity\PredefinedLobbyMessages;
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

class PredefinedMessages extends AbstractExtension
{
    public function __construct(
        private EntityManagerInterface $entityManager
    )
    {
    }

    public function getFunctions(): array
    {

        return [
            new TwigFunction('getPredefinedMessages', [$this, 'getPredefinedMessages']),

        ];
    }

    public function getPredefinedMessages()
    {

        return $this->entityManager->getRepository(PredefinedLobbyMessages::class)->findBy(['active' => true], ['priority' => 'ASC']);
    }
}
