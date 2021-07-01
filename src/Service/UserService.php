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
    private $userAddService;
    private $userEditService;
    private $userRemoveService;
    public function __construct(UserServiceRemoveRoom $userServiceRemoveRoom, UserServiceEditRoom $userEditService, UserNewRoomAddService $userNewRoomAddService, LicenseService $licenseService, PushService $pushService, EntityManagerInterface $entityManager, TranslatorInterface $translator, MailerService $mailerService, ParameterBagInterface $parameterBag, Environment $environment, NotificationService $notificationService, UrlGeneratorInterface $urlGenerator)
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
        $this->userRemoveService =  $userServiceRemoveRoom;
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
        if ($room->getScheduleMeeting()) {
            return $this->userAddService->addUserSchedule($user, $room);
        } elseif ($room->getPersistantRoom()) {
            //here come the persistant Email
        } else {
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
            return $this->userEditService->editRoom($user, $room);
        } else {
            return $this->userEditService->editRoomSchedule($user, $room);
        }

    }

    function removeRoom(User $user, Rooms $room)
    {
        if ($room->getScheduleMeeting()) {
            $this->userRemoveService->removeRoom($user,$room);
        } else {
            $this->userRemoveService->removeRoomScheduling($user,$room);
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
