<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\MyUser;
use App\Entity\Rooms;
use App\Entity\Server;
use App\Service\LicenseService;
use App\Service\MessageService;
use App\Service\ThemeService;
use App\Service\webhook\RoomStatusFrontendService;
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

class RoomStatus extends AbstractExtension
{
    private $webhookFrontend;
    public function __construct(RoomStatusFrontendService $roomStatusFrontendService)
    {
        $this->webhookFrontend = $roomStatusFrontendService;
    }

    public function getFunctions(): array
    {

        return [
            new TwigFunction('RoomStatusOpen', [$this, 'RoomStatusOpen']),
            new TwigFunction('RoomStatusOccupats', [$this, 'RoomStatusOccupats']),
            new TwigFunction('RoomStatusClosed', [$this, 'RoomStatusClosed']),
        ];
    }

    public function RoomStatusOpen(Rooms $rooms)
    {
        return $this->webhookFrontend->isRoomCreated($rooms);
    }

    public function RoomStatusOccupats(Rooms $rooms)
    {
        return $this->webhookFrontend->numberOfOccupants($rooms);
    }

    public function RoomStatusClosed(Rooms $rooms)
    {
        return $this->webhookFrontend->isRoomClosed($rooms);
    }
}
