<?php
// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\MyUser;
use App\Entity\User;
use App\Service\MessageService;
use App\Service\ServerUserManagment;
use App\Service\ThemeService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use function GuzzleHttp\Psr7\str;

class Server extends AbstractExtension
{


    private $serverUserManagment;
    public function __construct(ServerUserManagment $serverUserManagment)
    {
        $this->serverUserManagment = $serverUserManagment;
    }

    public function getFunctions(): array
    {

        return [
            new TwigFunction('getServer', [$this, 'getServer']),
        ];
    }

    public function getServer(User $user)
    {

        return $this->serverUserManagment->getServersFromUser($user);
    }
}