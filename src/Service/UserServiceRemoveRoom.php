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

class UserServiceRemoveRoom
{
    private $twig;
    private $notificationService;
    private $url;
    private $translator;
    private $urlGenerator;
    private $pushService;

    public function __construct(PushService $pushService, JoinUrlGeneratorService $joinUrlGeneratorService, TranslatorInterface $translator, Environment $environment, NotificationService $notificationService, UrlGeneratorInterface $urlGenerator)
    {
        $this->twig = $environment;
        $this->notificationService = $notificationService;
        $this->url = $urlGenerator;
        $this->translator = $translator;
        $this->urlGenerator = $joinUrlGeneratorService;
        $this->pushService = $pushService;
    }


    function removeRoom(User $user, Rooms $room)
    {

        $url = $this->urlGenerator->generateUrl($room, $user);
        $content = $this->twig->render('email/removeRoom.html.twig', ['user' => $user, 'room' => $room,]);
        $subject = $this->translator->trans('[Videokonferenz] Videokonferenz {name} abgesagt', ['{name}' => $room->getName()]);
        $ics = $this->notificationService->createIcs($room, $user, $url, 'CANCEL');
        $attachement[] = ['type' => 'text/calendar', 'filename' => substr(UtilsHelper::slugify($room->getName()), 0, 10) . '.ics', 'body' => $ics];
        $this->notificationService->sendNotification($content, $subject, $user, $room->getServer(), $room, $attachement);
        if ($room->getModerator() !== $user) {
            $this->pushService->generatePushNotification(
                $subject,
                $this->translator->trans(
                    'Die Videokonferenz {name} wurde von {organizer} abgesagt.',
                    ['{organizer}' => $room->getModerator()->getFirstName() . ' ' . $room->getModerator()->getLastName(),
                        '{name}' => $room->getName()]
                ),
                $user,
                $this->url->generate('dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL)
            );
        }
        return true;
    }

    function removePersistantRoom(User $user, Rooms $room)
    {
        $content = $this->twig->render('email/removeRoom.html.twig', ['user' => $user, 'room' => $room,]);
        $subject = $this->translator->trans('[Videokonferenz] Videokonferenz abgesagt');
        $this->notificationService->sendNotification($content, $subject, $user, $room->getServer(), $room);
        if (!UtilsHelper::isAllowedToOrganizeRoom($user, $room)) {
            $this->pushService->generatePushNotification(
                $subject,
                $this->translator->trans(
                    'Die Videokonferenz {name} wurde von {organizer} abgesagt.',
                    ['{organizer}' => $room->getModerator()->getFirstName() . ' ' . $room->getModerator()->getLastName(),
                        '{name}' => $room->getName()]
                ),
                $user,
                $this->url->generate('dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL)
            );
        }
        return true;
    }

    function removeRoomScheduling(User $user, Rooms $room)
    {

        $content = $this->twig->render('email/removeSchedule.html.twig', ['user' => $user, 'room' => $room,]);
        $subject = $this->translator->trans('[Terminplanung] Terminplanung abgesagt');
        $this->notificationService->sendNotification($content, $subject, $user, $room->getServer(), $room);
        return true;
    }
}
