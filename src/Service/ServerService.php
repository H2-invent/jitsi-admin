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
use App\UtilsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ServerService
{
    private EntityManagerInterface $em;
    private NotificationService $notification;
    private Environment $twig;
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator, EntityManagerInterface $entityManager, Environment $environment, NotificationService $notificationService)
    {
        $this->em = $entityManager;
        $this->notification = $notificationService;
        $this->twig = $environment;
        $this->translator = $translator;
    }

    function addPermission(Server $server, User $user): bool
    {
        $content = $this->twig->render('email/serverPermission.html.twig', ['user' => $user, 'server' => $server]);
        $subject = $this->translator->trans('[Serverorganisation] Sie wurden zu einem Jitsi-Meet-Server hinzugefügt');
        $this->notification->sendNotification($content, $subject, $user, $server);

        return true;
    }
    function makeSlug(string $urlString): ?string
    {
        $counter = 0;
        $slug = UtilsHelper::slugify($urlString);
        $slug = preg_replace('/[^\w\-\ ]/', '', $slug);
        $tmp = $slug;
        while (true) {
            $server = $this->em->getRepository(Server::class)->findOneBy(['slug' => $tmp]);
            if (!$server) {
                return $tmp;
            } else {
                $counter++;
                $tmp = $slug . '-' . $counter;
            }
        }
    }
}
