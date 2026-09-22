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
use App\UtilsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class UserServiceEditRoom
{
    private Environment $twig;
    private NotificationService $notificationService;
    private UrlGeneratorInterface $url;
    private TranslatorInterface $translator;
    private PushService $pushService;
    private JoinUrlGeneratorService $urlGenerator;

    public function __construct(JoinUrlGeneratorService $joinUrlGeneratorService, PushService $pushService, TranslatorInterface $translator, Environment $environment, NotificationService $notificationService, UrlGeneratorInterface $urlGenerator)
    {
        $this->twig = $environment;
        $this->notificationService = $notificationService;
        $this->url = $urlGenerator;
        $this->translator = $translator;
        $this->pushService = $pushService;
        $this->urlGenerator = $joinUrlGeneratorService;
    }

    function editRoom(User $user, Rooms $room): bool
    {

        $url = $this->urlGenerator->generateUrl($room, $user);
        $content = $this->twig->render('email/editRoom.html.twig', ['user' => $user, 'room' => $room, 'url' => $url]);
        $subject = $this->translator->trans('[Videokonferenz] Videokonferenz {name} wurde bearbeitet', ['{name}' => $room->getName()]);
        $ics = $this->notificationService->createIcs($room, $user, $url, 'REQUEST');
        $attachement[] = ['type' => 'text/calendar', 'filename' => substr(UtilsHelper::slugify($room->getName()), 0, 10) . '.ics', 'body' => $ics];
        $this->notificationService->sendNotification($content, $subject, $user, $room->getServer(), $room, $attachement);
        if ($room->getModerator() !== $user) {
            $this->pushService->generatePushNotification(
                $subject,
                $this->translator->trans(
                    'Sie wurden zu der Videokonferenz {name} von {organizer} eingeladen.',
                    ['{organizer}' => $room->getModerator()->getFirstName() . ' ' . $room->getModerator()->getLastName(),
                        '{name}' => $room->getName()]
                ),
                $user,
                $this->url->generate('dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL)
            );
        }

        return true;
    }
    function editPersistantRoom(User $user, Rooms $room): bool
    {

        $url = $this->urlGenerator->generateUrl($room, $user);
        $content = $this->twig->render('email/editRoom.html.twig', ['user' => $user, 'room' => $room, 'url' => $url]);
        $subject = $this->translator->trans('[Videokonferenz] Videokonferenz {name} wurde bearbeitet', ['{name}' => $room->getName()]);
        $this->notificationService->sendNotification($content, $subject, $user, $room->getServer(), $room);
        if ($room->getModerator() !== $user) {
            $this->pushService->generatePushNotification(
                $subject,
                $this->translator->trans(
                    'Sie wurden zu der Videokonferenz {name} von {organizer} eingeladen.',
                    ['{organizer}' => $room->getModerator()->getFirstName() . ' ' . $room->getModerator()->getLastName(),
                        '{name}' => $room->getName()]
                ),
                $user,
                $this->url->generate('dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL)
            );
        }

        return true;
    }
    function editRoomSchedule(User $user, Rooms $room): bool
    {

        //we have a shedule Meting. the participants only got a link to shedule their appointments
        $content = $this->twig->render('email/scheduleMeeting.html.twig', ['user' => $user, 'room' => $room,]);
        $subject = $this->translator->trans('[Terminplanung] Neue Einladung zu der Terminplanung {name}', ['{name}' => $room->getName()]);
        $this->notificationService->sendNotification($content, $subject, $user, $room->getServer(), $room);

        return true;
    }
}
