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

    function notifyUser(User $user, Rooms $room)
    {
        $url = $this->urlGenerator->generateUrl($room, $user);
        $content = $this->twig->render('email/rememberUser.html.twig', ['user' => $user, 'room' => $room, 'url' => $url]);
        $subject = $this->translator->trans('Videokonferenz {room} startet gleich', array('{room}' => $room->getName()));
        $this->notificationService->sendCron($content, $subject, $user, $room->getServer());
        $url = $this->url->generate('join_index_no_slug', array(), UrlGeneratorInterface::ABSOLUTE_URL);
        if ($this->licenseService->verify($room->getServer())) {
            $url = $this->url->generate('join_index', array('slug' => $room->getServer()->getSlug()), UrlGeneratorInterface::ABSOLUTE_URL);
        }
        $this->pushService->generatePushNotification(
            $subject,
            $this->translator->trans('Die Videokonferenz {name} von startet gleich.',
                array('{organizer}' => $room->getModerator()->getFirstName() . ' ' . $room->getModerator()->getLastName(),
                    '{name}' => $room->getName())),
            $user,
            $url
        );
        return true;
    }


}
