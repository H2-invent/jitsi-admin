<?php
/**
 * Created by PhpStorm.
 * User: andreas.holzmann
 * Date: 06.06.2020
 * Time: 19:01
 */

namespace App\Service;


use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;


class ServerService
{
    private $em;
    private $logger;
    private $notification;
    private $twig;
    private $translator;

    public function __construct(TranslatorInterface $translator, EntityManagerInterface $entityManager, Environment $environment, LoggerInterface $logger, NotificationService $notificationService)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
        $this->notification = $notificationService;
        $this->twig = $environment;
        $this->translator = $translator;
    }

    function addPermission(Server $server, User $user)
    {
        $content = $this->twig->render('email/serverPermission.html.twig', ['user' => $user, 'server' => $server]);
        $subject = $this->translator->trans('Sie wurden zu einem Jitsi Meet Server hinzugefügt');
        $this->notification->sendNotification($content, $subject, $user);

        return true;
    }
}
