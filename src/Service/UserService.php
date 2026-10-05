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
use App\Service\Caller\CallerPrepareService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class UserService
{
    public function __construct(private readonly CreateHttpsUrl          $createHttpsUrl, private readonly CallerPrepareService    $callerUserService, private readonly UserServiceRemoveRoom   $userRemoveService, private readonly UserServiceEditRoom     $userEditService, private readonly UserNewRoomAddService   $userAddService, private readonly LicenseService          $licenseService, private readonly PushService             $pushService, private readonly EntityManagerInterface  $em, private readonly TranslatorInterface     $translator, private readonly ParameterBagInterface   $parameterBag, private readonly Environment             $twig, private readonly NotificationService     $notificationService, private readonly UrlGeneratorInterface   $url, private readonly JoinUrlGeneratorService $joinUrlGenerator)
    {
    }

    public function generateUrl(Rooms $room, User $user): string
    {
        return $this->joinUrlGenerator->generateUrl($room, $user);
    }

    public function addUser(User $user, Rooms $room): bool
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

    public function addWaitinglist(User $user, Rooms $room): bool
    {
        if (!$user->getUid()) {
            $user->setUid(md5(uniqid()));
            $this->em->persist($user);
            $this->em->flush();
        }
        return $this->userAddService->addWaitinglist($user, $room);
    }

    public function editRoom(User $user, Rooms $room): bool
    {
        if ($room->getScheduleMeeting()) {
            return $this->userEditService->editRoomSchedule($user, $room);
        } elseif ($room->getPersistantRoom()) {
            return $this->userEditService->editPersistantRoom($user, $room);
        } else {
            return $this->userEditService->editRoom($user, $room);
        }
    }

    public function removeRoom(User $user, Rooms $room): bool
    {
        if ($room->getScheduleMeeting()) {
            $this->userRemoveService->removeRoomScheduling($user, $room);
        } elseif ($room->getPersistantRoom()) {
            return $this->userRemoveService->removePersistantRoom($user, $room);
        } else {
            if ($room->getEnddate() > new \DateTimeImmutable()) {
                $this->userRemoveService->removeRoom($user, $room);
            }
        }
        return true;
    }

    public function notifyUser(User $user, Rooms $room): bool
    {
        $url = $this->generateUrl($room, $user);
        $content = $this->twig->render('email/rememberUser.html.twig', ['user' => $user, 'room' => $room, 'url' => $url]);
        $subject = $this->translator->trans('[Erinnerung] Videokonferenz {room} startet gleich', ['{room}' => $room->getName()]);
        $this->notificationService->sendCron($content, $subject, $user, $room->getServer(), $room);


        $url = $this->createHttpsUrl->createHttpsUrl($this->url->generate('join_index_no_slug', []), $room);

        if ($this->licenseService->verify($room->getServer())) {
            $url = $this->createHttpsUrl->createHttpsUrl($this->url->generate('join_index', ['slug' => $room->getServer()->getSlug()]), $room);
        }

        /** @var string $showNameFrontend */
        $showNameFrontend = $this->parameterBag->get('laf_showNameFrontend');
        $this->pushService->generatePushNotification(
            $subject,
            $this->translator->trans(
                'Die Videokonferenz {name} startet gleich.',
                ['{organizer}' => $room->getModerator()->getFormatedName($showNameFrontend),
                    '{name}' => $room->getName()]
            ),
            $user,
            $url
        );
        return true;
    }
}
