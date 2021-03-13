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
use Symfony\Contracts\Translation\TranslatorInterface;


class RoomService
{
    private $em;
    private $logger;
    private $translator;
    public function __construct(TranslatorInterface $translator, EntityManagerInterface $entityManager, FormFactoryInterface $formBuilder, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
        $this->translator = $translator;

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
        $serverUrl = $room->getServer()->getUrl();
        $serverUrl = str_replace('https://','',$serverUrl);
        $serverUrl = str_replace('http://','',$serverUrl);
        $jitsi_server_url = $type . $serverUrl;
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


    function transferModerator(User $oldUser,User $user, Rooms $rooms){
        if($rooms->getModerator() === $oldUser){
            $rooms->setModerator($user);
            $this->em->persist($rooms);
            $this->em->flush();
            return true;
        }
        return false;
    }
}
