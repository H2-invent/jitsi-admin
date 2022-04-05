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
use App\UtilsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use phpDocumentor\Reflection\Types\This;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;


/**
 * Class RoomService
 * @package App\Service
 */
class RoomService
{
    private $em;
    private $logger;
    private $translator;
    private $uploadHelper;

    public function __construct(UploaderHelper $uploaderHelper, TranslatorInterface $translator, EntityManagerInterface $entityManager, FormFactoryInterface $formBuilder, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->uploadHelper = $uploaderHelper;

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
    function join(Rooms $room, ?User $user, $t, $userName)
    {
        $roomUser = $this->em->getRepository(RoomsUser::class)->findOneBy(array('user' => $user, 'room' => $room));
        if (!$roomUser) {
            $roomUser = new RoomsUser();
        }
        $moderator = false;
        if ($room->getModerator() === $user || $roomUser->getModerator()) {
            $moderator = true;
        }
        $avatar = null;
        if ($user && $user->getProfilePicture()){
            $avatar =  $this->uploadHelper->asset($user->getProfilePicture(),'documentFile');
        }
        $url = $this->createUrl($t, $room, $moderator, $roomUser, $userName,$avatar);
        return $url;
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
    function joinUrl($t, Rooms $room, $name, $isModerator)
    {
        return $this->createUrl($t, $room, $isModerator, null, $name);
    }

    public function createUrl($t, Rooms $room, $isModerator, ?RoomsUser $roomUser, $userName, $avatar = null)
    {
        if ($t === 'a') {
            $type = 'jitsi-meet://';
        } else {
            $type = 'https://';
        }
        $serverUrl = $room->getServer()->getUrl();
        $serverUrl = str_replace('https://', '', $serverUrl);
        $serverUrl = str_replace('http://', '', $serverUrl);
        $jitsi_server_url = $type . $serverUrl;
        $jitsi_jwt_token_secret = $room->getServer()->getAppSecret();
        $token = JWT::encode($this->genereateJwtPayload($userName, $room, $room->getServer(), $isModerator, $roomUser,$avatar), $jitsi_jwt_token_secret);
        $url = $jitsi_server_url . '/' . $room->getUid();
        if ($room->getServer()->getAppId() && $room->getServer()->getAppSecret()) {
            $url = $url . '?jwt=' . $token;
        }
        $url = $url . '#config.subject=%22' . UtilsHelper::slugify($room->getName()) . '%22';
        return $url;
    }

    public function generateJwt(Rooms $room, ?User $user, $userName)
    {
        $roomUser = $this->em->getRepository(RoomsUser::class)->findOneBy(array('user' => $user, 'room' => $room));
        if (!$roomUser) {
            $roomUser = new RoomsUser();
        }
        $moderator = false;
        if ($room->getModerator() === $user || $roomUser->getModerator()) {
            $moderator = true;
        }
        $avatar = null;
        if($user && $user->getProfilePicture()){
            $avatar = $this->uploadHelper->asset($user->getProfilePicture(),'documentFile');
        }
        return JWT::encode($this->genereateJwtPayload($userName, $room, $room->getServer(), $moderator, $roomUser,$avatar), $room->getServer()->getAppSecret());
    }

    public function genereateJwtPayload($userName, Rooms $room, Server $server, $moderator, RoomsUser $roomUser = null,$avatar = null)
    {
        if(!$server->getAppId()){
            return null;
        }
        $payload = array(
            "aud" => "jitsi_admin",
            "iss" => $room->getServer()->getAppId(),
            "sub" => $room->getServer()->getUrl(),
            "room" => $room->getUid(),
            "context" => [
                'user' => [
                    'name' => $userName,
                ],
            ],

        );
        if ($roomUser && !$avatar){
            if ($roomUser->getUser() && $roomUser->getUser()->getProfilePicture()){
                $avatar =  $this->uploadHelper->asset($roomUser->getUser()->getProfilePicture(),'documentFile');
            }
        }
        if ($avatar){
            $payload['context']['user']['avatar'] = $avatar;
        }
        if ($room->getServer()->getJwtModeratorPosition() == 0) {
            $payload['moderator'] = $moderator;
        } elseif ($room->getServer()->getJwtModeratorPosition() == 1) {
            $payload['context']['user']['moderator'] = $moderator;
        }
        $screen = array(
            'screen-sharing' => true,
            'private-message' => true,

        );
        if ($room->getServer()->getFeatureEnableByJWT()) {
            if ($room->getDissallowScreenshareGlobal()) {
                $screen['screen-sharing'] = false;
                if (($roomUser && $roomUser->getShareDisplay()) || $moderator) {
                    $screen['screen-sharing'] = true;

                }
            }
            if ($room->getDissallowPrivateMessage()) {
                $screen['private-message'] = false;
                if (($roomUser && $roomUser->getPrivateMessage()) || $moderator) {
                    $screen['private-message'] = true;
                }
            }
            $payload['context']['features'] = $screen;
        }
        return $payload;
    }
}
