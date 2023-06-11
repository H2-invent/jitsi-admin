<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\MyUser;
use App\Entity\Server;
use App\Service\LicenseService;
use App\Service\MessageService;
use DeviceDetector\ClientHints;
use DeviceDetector\DeviceDetector;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use function GuzzleHttp\Psr7\str;

class BrowserCompability extends AbstractExtension
{


    public function __construct()
    {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('isFirefox', [$this, 'isFirefox']),
            new TwigFunction('isOSType', [$this, 'isOSType']),
        ];
    }

    public function isFirefox(): bool
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT']; // change this to the useragent you want to parse
        $clientHints = ClientHints::factory($_SERVER); // client hints are optional

        $dd = new DeviceDetector($userAgent, $clientHints);
        $dd->parse();

        $clientInfo = $dd->getClient(); // holds information about browser, feed reader, media player, ...
        return strtolower($clientInfo['name'])=='firefox';
    }
    public function isOSType($osType): bool
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT']; // change this to the useragent you want to parse
        $clientHints = ClientHints::factory($_SERVER); // client hints are optional

        $dd = new DeviceDetector($userAgent, $clientHints);
        $dd->parse();

        $clientInfo = $dd->getOs(); // holds information about browser, feed reader, media player, ...
        return strtolower($clientInfo['name'])==$osType;
    }


}
