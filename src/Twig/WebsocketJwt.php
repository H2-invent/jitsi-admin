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
use App\Service\Websocket\WebsocketJwtService;
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

class WebsocketJwt extends AbstractExtension
{



    public function __construct(private WebsocketJwtService $websocketJwtService)
    {

    }

    public function getFunctions(): array
    {

        return [
            new TwigFunction('getJwtforWebsocket', [$this, 'getJwtforWebsocket']),
        ];
    }

    public function getJwtforWebsocket($rooms, $userId)
    {
        return $this->websocketJwtService->createJwt($rooms,$userId);
    }


}