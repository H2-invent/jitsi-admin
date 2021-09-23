<?php
/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 03.10.2019
 * Time: 19:01
 */

namespace App\Service;

use App\Entity\Rooms;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;


class UserServiceNotify
{


    private $twig;
    private $notificationService;
    private $url;
    private $translator;
    private $pushService;
    private $licenseService;
    private $urlGenerator;

    public function __construct(JoinUrlGeneratorService $joinUrlGeneratorService, LicenseService $licenseService, PushService $pushService, TranslatorInterface $translator, Environment $environment, NotificationService $notificationService, UrlGeneratorInterface $urlGenerator)
    {

        $this->urlGenerator = $joinUrlGeneratorService;
        $this->twig = $environment;
        $this->notificationService = $notificationService;
        $this->url = $urlGenerator;
        $this->translator = $translator;
        $this->pushService = $pushService;
        $this->licenseService = $licenseService;

    }



}
