<?php
// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\MyUser;
use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Service\Jigasi\JigasiService;
use App\Service\LicenseService;
use App\Service\MessageService;
use App\Service\StartMeetingService;
use App\Service\ThemeService;
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

class CheckStartTime extends AbstractExtension
{


    public function __construct(private StartMeetingService $startMeetingService)
    {

    }

    public function getFunctions(): array
    {

        return [
            new TwigFunction('isRoomOpen', [$this, 'isRoomOpen']),
        ];
    }

    public function isRoomOpen(Rooms $room, ?User $user)
    {

        $isOpen= StartMeetingService::checkTime($room,$user);
        if (!$isOpen){
            return $this->startMeetingService->buildClosedString($room);
        }else{
            return true;
        }
    }


}