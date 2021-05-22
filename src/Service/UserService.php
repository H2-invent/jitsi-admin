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


class UserService
{
    private $mailer;
    private $parameterBag;
    private $twig;
    private $notificationService;
    private $url;
    private $translator;
    private $em;
    private $pushService;
    private $licenseService;

    public function __construct(LicenseService $licenseService, PushService $pushService, EntityManagerInterface $entityManager, TranslatorInterface $translator, MailerService $mailerService, ParameterBagInterface $parameterBag, Environment $environment, NotificationService $notificationService, UrlGeneratorInterface $urlGenerator)
    {
        $this->mailer = $mailerService;
        $this->parameterBag = $parameterBag;
        $this->twig = $environment;
        $this->notificationService = $notificationService;
        $this->url = $urlGenerator;
        $this->translator = $translator;
        $this->em = $entityManager;
        $this->pushService = $pushService;
        $this->licenseService = $licenseService;
    }

    function generateUrl(Rooms $room, User $user)
    {

        $data = base64_encode('uid=' . $room->getUid() . '&email=' . $user->getEmail());
        $url = $this->parameterBag->get('laF_baseUrl') . $this->url->generate('join_index', ['data' => $data, 'slug' => $room->getServer()->getSlug()]);
        return $url;
    }

    function addUser(User $user, Rooms $room)
    {
        if (!$user->getUid()) {
            $user->setUid(md5(uniqid()));
            $this->em->persist($user);
            $this->em->flush();
        }
        if (!$room->getScheduleMeeting()) {
            //we have a not sheduled meeting. So the participabts are getting invited directly
            $url = $this->generateUrl($room, $user);
            $content = $this->twig->render('email/addUser.html.twig', ['user' => $user, 'room' => $room, 'url' => $url]);
            $subject = $this->translator->trans('Neue Einladung zu einer Videokonferenz');
            $ics = $this->notificationService->createIcs($room, $user, $url, 'REQUEST');
            $attachement[] = array('type' => 'text/calendar', 'filename' => $room->getName() . '.ics', 'body' => $ics);
            $this->notificationService->sendNotification($content, $subject, $user, $room->getServer(), $attachement);
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

        } else {
            //we have a shedule Meting. the participants only got a link to shedule their appointments
            $content = $this->twig->render('email/scheduleMeeting.html.twig', ['user' => $user, 'room' => $room,]);
            $subject = $this->translator->trans('Neue Einladung zu einer Terminplanung');
            $this->notificationService->sendNotification($content, $subject, $user, $room->getServer());
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
        }
        return true;
    }

    function addWaitinglist(User $user, Rooms $room)
    {
        if (!$user->getUid()) {
            $user->setUid(md5(uniqid()));
            $this->em->persist($user);
            $this->em->flush();
        }
        //we have a not sheduled meeting. So the participabts are getting invited directly
        $content = $this->twig->render('email/waitingList.html.twig', ['user' => $user, 'room' => $room]);
        $subject = $this->translator->trans('Hinzugefügt zur Warteliste');
        $this->notificationService->sendNotification($content, $subject, $user, $room->getServer());
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

    function editRoom(User $user, Rooms $room)
    {
        if (!$room->getScheduleMeeting()) {
            $url = $this->generateUrl($room, $user);
            $content = $this->twig->render('email/editRoom.html.twig', ['user' => $user, 'room' => $room, 'url' => $url]);
            $subject = $this->translator->trans('Videokonferenz wurde bearbeitet');
            $ics = $this->notificationService->createIcs($room, $user, $url, 'REQUEST');
            $attachement[] = array('type' => 'text/calendar', 'filename' => $room->getName() . '.ics', 'body' => $ics);
            $this->notificationService->sendNotification($content, $subject, $user, $room->getServer(), $attachement);
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
        } else {
            //we have a shedule Meting. the participants only got a link to shedule their appointments
            $content = $this->twig->render('email/scheduleMeeting.html.twig', ['user' => $user, 'room' => $room,]);
            $subject = $this->translator->trans('Neue Einladung zu einer Terminplanung');
            $this->notificationService->sendNotification($content, $subject, $user, $room->getServer());
        }
        return true;
    }

    function removeRoom(User $user, Rooms $room)
    {
        if (!$room->getScheduleMeeting()) {
            $url = $this->generateUrl($room, $user);
            $content = $this->twig->render('email/removeRoom.html.twig', ['user' => $user, 'room' => $room,]);
            $subject = $this->translator->trans('Videokonferenz abgesagt');
            $ics = $this->notificationService->createIcs($room, $user, $url, 'CANCEL');
            $attachement[] = array('type' => 'text/calendar', 'filename' => $room->getName() . '.ics', 'body' => $ics);
            $this->notificationService->sendNotification($content, $subject, $user, $room->getServer(), $attachement);
            if ($room->getModerator() !== $user) {
                $this->pushService->generatePushNotification(
                    $subject,
                    $this->translator->trans('Die Videokonferenz {name} wurde von {organizer} abgesagt.',
                        array('{organizer}' => $room->getModerator()->getFirstName() . ' ' . $room->getModerator()->getLastName(),
                            '{name}' => $room->getName())),
                    $user,
                    $this->url->generate('dashboard', array(), UrlGeneratorInterface::ABSOLUTE_URL)
                );
            }
        } else {
            $content = $this->twig->render('email/removeSchedule.html.twig', ['user' => $user, 'room' => $room,]);
            $subject = $this->translator->trans('Terminplanung abgesagt');
            $this->notificationService->sendNotification($content, $subject, $user, $room->getServer());
        }
        return true;
    }

    function notifyUser(User $user, Rooms $room)
    {
        $url = $this->generateUrl($room, $user);
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
