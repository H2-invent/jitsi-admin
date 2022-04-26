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


class UserNewRoomAddService
{
    private $twig;
    private $notificationService;
    private $url;
    private $translator;
    private $em;
    private $pushService;
    private $urlGenerator;
    public function __construct(JoinUrlGeneratorService $joinUrlGeneratorService,  PushService $pushService, EntityManagerInterface $entityManager, TranslatorInterface $translator,  Environment $environment, NotificationService $notificationService, UrlGeneratorInterface $urlGenerator)
    {
        $this->twig = $environment;
        $this->notificationService = $notificationService;
        $this->url = $urlGenerator;
        $this->translator = $translator;
        $this->em = $entityManager;
        $this->pushService = $pushService;
        $this->urlGenerator = $joinUrlGeneratorService;
    }


    /**
     * we have a not sheduled meeting. So the participabts are getting invited directly
     * @param User $user
     * @param Rooms $room
     * @return bool
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    function addUserToRoom(User $user, Rooms $room)
    {
        $url = $this->urlGenerator->generateUrl($room, $user);
        $content = $this->twig->render('email/addUser.html.twig', ['user' => $user, 'room' => $room, 'url' => $url]);
        $subject = $this->translator->trans('[Videokonferenz] Neue Einladung zur Videokonferenz {name}',array('{name}'=>$room->getName()));
        $ics = $this->notificationService->createIcs($room, $user, $url, 'REQUEST');
        $attachement[] = array('type' => 'text/calendar', 'filename' => substr(UtilsHelper::slugify($room->getName()),0,10) . '.ics', 'body' => $ics);
        $this->notificationService->sendNotification($content, $subject, $user, $room->getServer(),$room,$attachement);
        if ($room->getModerator() !== $user) {
            $this->pushService->generatePushNotification(
                $subject,
                $this->translator->trans('Sie wurden zu der Videokonferenz {name} von {organizer} eingeladen.',
                    array('{organizer}' => $room->getModerator()->getFirstName() . ' ' . $room->getModerator()->getLastName(),
                        '{name}' => $room->getName())),
                $user,
                $this->url->generate('dashboard', array(), UrlGeneratorInterface::ABSOLUTE_URL),
                $room->getUid()
            );
        }
        return true;
    }

    /**
     * we have a persistant Room. So the participabts are getting invited directly
     * @param User $user
     * @param Rooms $room
     * @return bool
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    function addUserToPersistantRoom(User $user, Rooms $room)
    {

        $url = $this->urlGenerator->generateUrl($room, $user);
        $content = $this->twig->render('email/addUser.html.twig', ['user' => $user, 'room' => $room, 'url' => $url]);
        $subject = $this->translator->trans('[Videokonferenz] Neue Einladung zur Videokonferenz {name}',array('{name}'=>$room->getName()));
        $this->notificationService->sendNotification($content, $subject, $user, $room->getServer(),$room);
        if ($room->getModerator() !== $user) {
            $this->pushService->generatePushNotification(
                $subject,
                $this->translator->trans('Sie wurden zu der Videokonferenz {name} von {organizer} eingeladen.',
                    array('{organizer}' => $room->getModerator()->getFirstName() . ' ' . $room->getModerator()->getLastName(),
                        '{name}' => $room->getName())),
                $user,
                $this->url->generate('dashboard', array(), UrlGeneratorInterface::ABSOLUTE_URL)
            );
        }

        return true;
    }

    /**
     * we have a shedule Meting. the participants only got a link to shedule their appointments
     * @param User $user
     * @param Rooms $room
     * @return bool
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    function addUserSchedule(User $user, Rooms $room)
    {

        $content = $this->twig->render('email/scheduleMeeting.html.twig', ['user' => $user, 'room' => $room,]);
        $subject = $this->translator->trans('[Terminplanung] Neue Einladung zur Terminplanung {name}',array('{name}'=>$room->getName()));
        $this->notificationService->sendNotification($content, $subject, $user, $room->getServer(),$room);
        if ($room->getModerator() !== $user) {
            $this->pushService->generatePushNotification(
                $subject,
                $this->translator->trans('Sie wurden zu der Terminplanung {name} von {organizer} eingeladen.',
                    array('{organizer}' => $room->getModerator()->getFirstName() . ' ' . $room->getModerator()->getLastName(),
                        '{name}' => $room->getName())),
                $user,
                $this->url->generate('schedule_public_main', array('scheduleId' => $room->getUid(), 'userId' => $user->getUid()), UrlGeneratorInterface::ABSOLUTE_URL)
            );
        }
        return true;
    }

    /**
     * we have a not sheduled meeting. So the participabts are getting invited directly
     * @param User $user
     * @param Rooms $room
     * @return bool
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    function addWaitinglist(User $user, Rooms $room)
    {
        $content = $this->twig->render('email/waitingList.html.twig', ['user' => $user, 'room' => $room]);
        $subject = $this->translator->trans('[Videokonferenz] Hinzugefügt zur Warteliste');
        $this->notificationService->sendNotification($content, $subject, $user, $room->getServer(),$room);
        if ($room->getModerator() !== $user) {
            $this->pushService->generatePushNotification(
                $subject,
                $this->translator->trans('Sie wurden auf die Warteliste für:  {name} hinzugefügt ',
                    array('{name}' => $room->getName())),
                $user,
                $this->url->generate('dashboard', array(), UrlGeneratorInterface::ABSOLUTE_URL)
            );
        }
        return true;
    }
}
