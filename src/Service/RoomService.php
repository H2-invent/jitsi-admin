<?php
/**
 * Created by PhpStorm.
 * User: andreas.holzmann
 * Date: 06.06.2020
 * Time: 19:01
 */

namespace App\Service;


use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;


class RoomService
{
    private $em;
    private $logger;
    private $userService;

    public function __construct(EntityManagerInterface $entityManager, FormFactoryInterface $formBuilder, LoggerInterface $logger, UserService $userService)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
        $this->userService = $userService;
    }

    function join(Rooms $room, User $user, $t, $userName)
    {
        if ($t === 'a') {
            $type = 'jitsi-meet://';
        } else {
            $type = 'https://';
        }
        if ($room->getModerator() === $user) {
            $moderator = true;
        } else {
            $moderator = false;
        }

        $jitsi_server_url = $type . $room->getServer()->getUrl();
        $jitsi_jwt_token_secret = $room->getServer()->getAppSecret();

        $payload = array(
            "aud" => "jitsi_admin",
            "iss" => $room->getServer()->getAppId(),
            "sub" => $room->getServer()->getUrl(),
            "room" => $room->getUid(),
            "context" => [
                'user' => [
                    'name' => $userName
                ]
            ],
            "moderator" => $moderator
        );

        $token = JWT::encode($payload, $jitsi_jwt_token_secret);
        if (!$room->getServer()->getAppId() || !$room->getServer()->getAppSecret()) {
            $url = $jitsi_server_url . '/' . $room->getUid();
        } else {
            $url = $jitsi_server_url . '/' . $room->getUid() . '?jwt=' . $token;
        }

        return $url;
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
            $this->userService->editRoom($user,$room);
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
        $this->em->flush();
        return $room;
    }
}
