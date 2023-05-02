<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\MyUser;
use App\Entity\Server;
use App\Entity\User;
use App\Service\LicenseService;
use App\Service\MessageService;
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

class Time extends AbstractExtension
{
    private $themeService;
    public function __construct(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    public function getFunctions(): array
    {

        return [
            new TwigFunction('getTime', [$this, 'getTime']),
        ];
    }

    public function getTime(User $user)
    {
        $now = new \DateTime('now', new \DateTimeZone($user->getTimeZone()));
        return $now;
    }
}
