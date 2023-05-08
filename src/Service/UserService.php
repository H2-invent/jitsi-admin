<?php

/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 03.10.2019
 * Time: 19:01
 */

namespace App\Service;

use App\Entity\CallerId;
use App\Entity\Rooms;
use App\Entity\User;
use App\Service\caller\CallerPrepareService;
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
    private $userAddService;
    private $userEditService;
    private $userRemoveService;
    private $callerUserService;
    private $createHttpsUrl;
    private $joinUrlGenerator;

    public function __construct(
        CreateHttpsUrl          $createHttpsUrl,
        CallerPrepareService    $callerPrepareService,
        UserServiceRemoveRoom   $userServiceRemoveRoom,
        UserServiceEditRoom     $userEditService,
        UserNewRoomAddService   $userNewRoomAddService,
        LicenseService          $licenseService,
        PushService             $pushService,
        EntityManagerInterface  $entityManager,
        TranslatorInterface     $translator,
        MailerService           $mailerService,
        ParameterBagInterface   $parameterBag,
        Environment             $environment,
        NotificationService     $notificationService,
        UrlGeneratorInterface   $urlGenerator,
        JoinUrlGeneratorService $joinUrlGeneratorService
    )
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
        $this->userAddService = $userNewRoomAddService;
        $this->userEditService = $userEditService;
        $this->userRemoveService = $userServiceRemoveRoom;
        $this->callerUserService = $callerPrepareService;
        $this->createHttpsUrl = $createHttpsUrl;
        $this->joinUrlGenerator = $joinUrlGeneratorService;
    }

    function generateUrl(Rooms $room, User $user)
    {
        return $this->joinUrlGenerator->generateUrl($room, $user);
    }

    function addUser(User $user, Rooms $room)
    {
        if (!$user->getUid()) {
            $user->setUid(md5(uniqid()));
            $this->em->persist($user);
            $this->em->flush();
        }

        if ($room->getScheduleMeeting()) {
            return $this->userAddService->addUserSchedule($user, $room);
        } elseif ($room->getPersistantRoom()) {
            $this->callerUserService->createUserCallerIDforRoom($room);
            return $this->userAddService->addUserToPersistantRoom($user, $room);
        } else {
            $this->callerUserService->createUserCallerIDforRoom($room);
            return $this->userAddService->addUserToRoom($user, $room);
        }
    }

    function addWaitinglist(User $user, Rooms $room)
    {
        if (!$user->getUid()) {
            $user->setUid(md5(uniqid()));
            $this->em->persist($user);
            $this->em->flush();
        }
        return $this->userAddService->addWaitinglist($user, $room);
    }

    function editRoom(User $user, Rooms $room)
    {
        if ($room->getScheduleMeeting()) {
            return $this->userEditService->editRoomSchedule($user, $room);
        } elseif ($room->getPersistantRoom()) {
            return $this->userEditService->editPersistantRoom($user, $room);
        } else {
            return $this->userEditService->editRoom($user, $room);
        }
    }

    function removeRoom(User $user, Rooms $room)
    {
        if ($room->getScheduleMeeting()) {
            $this->userRemoveService->removeRoomScheduling($user, $room);
        } elseif ($room->getPersistantRoom()) {
            return $this->userRemoveService->removePersistantRoom($user, $room);
        } else {
            if ($room->getEnddate() > new \DateTime()) {
                $this->userRemoveService->removeRoom($user, $room);
            }
        }
        return true;
    }

    function notifyUser(User $user, Rooms $room)
    {
        $url = $this->generateUrl($room, $user);
        $content = $this->twig->render('email/rememberUser.html.twig', ['user' => $user, 'room' => $room, 'url' => $url]);
        $subject = $this->translator->trans('[Erinnerung] Videokonferenz {room} startet gleich', ['{room}' => $room->getName()]);
        $this->notificationService->sendCron($content, $subject, $user, $room->getServer(), $room);


        $url = $this->createHttpsUrl->createHttpsUrl($this->url->generate('join_index_no_slug', []), $room);

        if ($this->licenseService->verify($room->getServer())) {
            $url = $this->createHttpsUrl->createHttpsUrl($this->url->generate('join_index', ['slug' => $room->getServer()->getSlug()]), $room);
        }

        $this->pushService->generatePushNotification(
            $subject,
            $this->translator->trans(
                'Die Videokonferenz {name} startet gleich.',
                ['{organizer}' => $room->getModerator()->getFormatedName($this->parameterBag->get('laf_showNameFrontend')),
                    '{name}' => $room->getName()]
            ),
            $user,
            $url
        );
        return true;
    }
}
