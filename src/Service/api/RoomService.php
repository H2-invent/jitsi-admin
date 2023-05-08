<?php

namespace App\Service\api;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Service\InviteService;
use App\Service\UserCreatorService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RoomService
{
    private $em;
    private $userService;
    private $inviteService;
    private $urlGenerator;
    private $userCreatorService;
    public function __construct(UserCreatorService $userCreatorService, UrlGeneratorInterface $urlGenerator, EntityManagerInterface $entityManager, UserService $userService, InviteService $inviteService)
    {
        $this->em = $entityManager;
        $this->userService = $userService;
        $this->inviteService = $inviteService;
        $this->urlGenerator = $urlGenerator;
        $this->userCreatorService = $userCreatorService;
    }

    public function createRoom(User $user, Server $server, \DateTime $start, $duration, $name)
    {
        // We initialize the Room with the data;

        $room = new Rooms();
        $room->setName($name);
        $room->addUser($user);
        $room->setDuration($duration);
        $room->setUid(rand(01, 99) . time());
        $room->setModerator($user);
        $room->setSequence(0);
        $room->setUidReal(md5(uniqid('h2-invent', true)));
        $room->setStart($start);
        $room->setEnddate((clone $room->getStart())->modify('+ ' . $room->getDuration() . ' minutes'));
        $room->setServer($server);

        $this->em->persist($room);
        $this->em->flush();
        $this->userService->addUser($room->getModerator(), $room);
        return $room;
    }

    public function editRoom(Rooms $room, Server $server, \DateTime $start, $duration, $name)
    {
        // We initialize the Room with the data;


        $room->setName($name);
        $room->setDuration($duration);
        $room->setSequence(0);
        $room->setStart($start);
        $room->setEnddate((clone $room->getStart())->modify('+ ' . $room->getDuration() . ' minutes'));
        $room->setServer($server);

        $this->em->persist($room);
        $this->em->flush();
        foreach ($room->getUser() as $user) {
            $this->userService->editRoom($user, $room);
        }
        return $room;
    }

    public function deleteRoom(Rooms $room)
    {
        // We delete the Room


        foreach ($room->getUser() as $user) {
            $this->userService->removeRoom($user, $room);
            $room->removeUser($user);
            $this->em->persist($room);
        }
        $room->setModerator(null);
        $this->em->persist($room);
        foreach ($room->getFavoriteUsers() as $data) {
            $data->removeFavorite($room);
            $this->em->persist($data);
        }

        $this->em->flush();
        return $room;
    }


    public function removeUserFromRoom(?Rooms $room, $email): array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['error' => true, 'text' => 'Email incorrect'];
        };
        if (!$room) {
            return ['error' => true, 'text' => 'no Room found'];
        };
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return ['error' => true, 'text' => 'User incorrect'];
        };
        if (in_array($user, $room->getUser()->toArray())) {
            $room->removeUser($user);

            $this->em->persist($room);
            $this->em->flush();
            $this->userService->removeRoom($user, $room);
        } else {
            return ['error' => true, 'text' => 'User incorrect'];
        }

        return ['uid' => $room->getUidReal(), 'user' => $email, 'error' => false, 'text' => 'Teilnehmer ' . $email . ' erfolgreich gelöscht'];
    }

    public function addUserToRoom(?Rooms $room, $email): array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['error' => true, 'text' => 'Email incorrect'];
        };
        if (!$room) {
            return ['error' => true, 'text' => 'no Room found'];
        };
//Here we get the User from an email if the user with the email does not exist, then we we create it
        $user = $this->userCreatorService->createUser($email, $email, '', '');
        if (!in_array($user, $room->getUser()->toArray())) {
            $user->addRoom($room);
            $user->addAddressbookInverse($room->getModerator());
            $this->em->persist($user);
            //Here we add the User to the room and send the email
            $this->userService->addUser($user, $room);
            $this->em->flush();
        }

        return ['uid' => $room->getUidReal(), 'user' => $email, 'error' => false, 'text' => 'Teilnehmer ' . $email . ' erfolgreich hinzugefügt'];
    }

    public function generateRoomInfo(Rooms $room): array
    {

        if (!$room) {
            return ['error' => true, 'text' => 'no Room found'];
        }
        $res = [];
        $user = [];
        foreach ($room->getUser() as $data) {
            $user[] = $data->getEmail();
        }
        $res['timeZone'] = $room->getTimeZoneAuto();
        $res['error'] = false;
        $res['teilnehmer'] = $user;
        $res['start'] = $room->getStart()->format('Y-m-dTH:i:s');
        $res['end'] = $room->getEnddate()->format('Y-m-dTH:i:s');
        $res['duration'] = $room->getDuration();
        $res['name'] = $room->getName();
        $res['moderator'] = $room->getModerator() ? $room->getModerator()->getEmail() : '';
        $res['server'] = $room->getServer()->getUrl();
        $res['joinBrowser'] = $this->urlGenerator->generate('room_join', ['t' => 'b', 'room' => $room->getId()], UrlGenerator::ABSOLUTE_URL);
        $res['joinApp'] = $this->urlGenerator->generate('room_join', ['t' => 'a', 'room' => $room->getId()], UrlGenerator::ABSOLUTE_URL);
        return $res;
    }
}
