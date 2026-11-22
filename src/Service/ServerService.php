<?php

/**
 * Created by PhpStorm.
 * User: andreas.holzmann
 * Date: 06.06.2020
 * Time: 19:01
 */

namespace App\Service;

use App\Entity\Server;
use App\Entity\User;
use App\UtilsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ServerService
{
    public function __construct(
        private readonly TranslatorInterface    $translator,
        private readonly EntityManagerInterface $em,
        private readonly Environment            $twig,
        private readonly NotificationService    $notification
    ) {
    }

    public function addPermission(Server $server, User $user): bool
    {
        $content = $this->twig->render('email/serverPermission.html.twig', ['user' => $user, 'server' => $server]);
        $subject = $this->translator->trans('[Serverorganisation] Sie wurden zu einem Jitsi-Meet-Server hinzugefügt');
        $this->notification->sendNotification($content, $subject, $user, $server);

        return true;
    }

    public function makeSlug(string $urlString): ?string
    {
        $counter = 0;
        $slug    = UtilsHelper::slugify($urlString);
        $slug    = preg_replace('/[^\w\-\ ]/', '', $slug);
        $tmp     = $slug;

        while (true) {
            $server = $this->em->getRepository(Server::class)->findOneBy(['slug' => $tmp]);
            if (!$server) {
                return $tmp;
            }

            $counter++;
            $tmp = $slug . '-' . $counter;
        }
    }

    public function cloneServerForAutoscaling(
        Server $server,
        string $url,
        string $name,
        string $appId,
        string $appSecret,
    ): Server
    {
        $newServer = clone $server;
        $newServer->setUrl($url)
            ->setServerName($name)
            ->setAppId($appId)
            ->setAppSecret($appSecret)
            ->setUpdatedAt(new \DateTimeImmutable())
            ->setIsAllowedToCloneForAutoscale(null)
            ->setSlug(urlencode($url))
        ;
        $newServer->getUser()->clear();
        $newServer->setAdministrator(null);
        $this->em->persist($newServer);
        $this->em->flush();

        return $newServer;
    }
}
