<?php
/**
 * Created by PhpStorm.
 * User: andreas.holzmann
 * Date: 06.06.2020
 * Time: 19:01
 */

namespace App\Service;


use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Entity\Server;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


/**
 * Class RoomService
 * @package App\Service
 */
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

    /**
     * Creates the JWT Token to send to the Information of the User to the jitsi-Meet Server
     * @param Rooms $room
     * @param User $user
     * @param $t
     * @param $userName
     * @return string
     * @author Emanuel Holzmann
     * @de
     */
    function join(Rooms $room, User $user, $t, $userName)
    {
        $roomUser = $this->em->getRepository(RoomsUser::class)->findOneBy(array('user' => $user, 'room' => $room));
        if(!$roomUser){
            $roomUser = new RoomsUser();
        }
        if ($t === 'a') {
            $type = 'jitsi-meet://';
        } else {
            $type = 'https://';
        }
        if ($room->getModerator() === $user || $roomUser->getModerator()) {
            $moderator = true;
        } else {
            $moderator = false;
        }
        $serverUrl = $room->getServer()->getUrl();
        $serverUrl = str_replace('https://', '', $serverUrl);
        $serverUrl = str_replace('http://', '', $serverUrl);
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
                ],
            ],
            "moderator" => $moderator
        );

        $screen = array(
                'screen-sharing' => true,
                'private-message' => true,

        );
        if ($room->getServer()->getFeatureEnableByJWT()) {
            if ($room->getDissallowScreenshareGlobal()) {
                $screen['screen-sharing'] = false;
                if (($roomUser && $roomUser->getShareDisplay()) || $user === $room->getModerator()) {
                    $screen['screen-sharing'] = true;

                }
            }
            if ($room->getDissallowPrivateMessage()) {
                $screen['private-message'] = false;
                if ($roomUser && $roomUser->getPrivateMessage() || $user === $room->getModerator()) {
                    $screen['private-message'] = true;
                }
            }
            $payload['context']['features'] = $screen;
        }
        $token = JWT::encode($payload, $jitsi_jwt_token_secret);
        if (!$room->getServer()->getAppId() || !$room->getServer()->getAppSecret()) {
            $url = $jitsi_server_url . '/' . $room->getUid();
        } else {
            $url = $jitsi_server_url . '/' . $room->getUid() . '?jwt=' . $token;
        }

        return $url;
    }

}
