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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormFactoryInterface;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
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

    private $uploadHelper;

    public function __construct(
        UploaderHelper                $uploaderHelper,
        EntityManagerInterface        $entityManager,
        FormFactoryInterface          $formBuilder,
        LoggerInterface               $logger,
        private ParameterBagInterface $parameterBag,
        private CacheInterface        $cache,
        private HttpClientInterface   $httpClient
    )
    {
        $this->em = $entityManager;
        $this->logger = $logger;
        $this->uploadHelper = $uploaderHelper;
    }

    public function setHttpClient($httpClient): RoomService
    {
        $this->httpClient = $httpClient;
        return $this;
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
        $roomUser = $this->em->getRepository(RoomsUser::class)->findOneBy(['user' => $user, 'room' => $room]);
        if (!$roomUser) {
            $roomUser = new RoomsUser();
        }
        $moderator = false;
        if ($room->getModerator() === $user || $roomUser->getModerator()) {
            $moderator = true;
        }
        $avatar = null;
        if ($user && $user->getProfilePicture()) {
            $avatar = $this->uploadHelper->asset($user->getProfilePicture(), 'documentFile');
        }
        $url = $this->createUrl($t, $room, $moderator, $roomUser, $userName, $avatar);
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
        $token = JWT::encode($this->genereateJwtPayload($userName, $room, $room->getServer(), $isModerator, $roomUser, $avatar), $jitsi_jwt_token_secret, 'HS256');
        $url = $jitsi_server_url . '/' . $room->getUid();
        if ($room->getServer()->getAppId() && $room->getServer()->getAppSecret()) {
            $url = $url . '?jwt=' . $token;
        }
        $url = $url . '#config.subject=%22' . UtilsHelper::slugify($room->getName()) . '%22';
        return $url;
    }

    public function generateJwt(Rooms $room, ?User $user, $userName, $moderatorExplizit = false)
    {
        $roomUser = $this->em->getRepository(RoomsUser::class)->findOneBy(['user' => $user, 'room' => $room]);
        if (!$roomUser) {
            $roomUser = new RoomsUser();
        }
        $moderator = false;
        if ($room->getModerator() === $user || $roomUser->getModerator()) {
            $moderator = true;
        }
        if ($moderatorExplizit === true) {
            $moderator = true;
        }
        $avatar = null;
        if ($user && $user->getProfilePicture()) {
            $avatar = $this->uploadHelper->asset($user->getProfilePicture(), 'documentFile');
        }
        return JWT::encode($this->genereateJwtPayload($userName, $room, $room->getServer(), $moderator, $roomUser, $avatar), $room->getServer()->getAppSecret(), 'HS256');
    }

    public function genereateJwtPayload($userName, Rooms $room, Server $server, $moderator, RoomsUser $roomUser = null, $avatar = null)
    {
        if (!$server->getAppId()) {
            return null;
        }


        $payload = [

            "aud" => "jitsi_admin",
            "iss" => $room->getServer()->getAppId(),
            "sub" => $room->getServer()->getUrl(),
            "room" => $room->getUid(),
            "context" => [
                'user' => [
                    'name' => $userName,
                ],
            ],

        ];

        if ($server->isLiveKitServer()) {
            $encSecret = $this->generateEncryptedSecret($server);
            if ($encSecret) {
                $payload['livekit'] = [
                    "host" => $server->getUrl(),
                    "key" => $server->getAppId(),
                ];
            }
        }
        if ($roomUser && !$avatar) {
            $this->logger->debug('profile picure is added to the jwt');
            if ($roomUser->getUser() && $roomUser->getUser()->getProfilePicture()) {
                $avatar = $this->uploadHelper->asset($roomUser->getUser()->getProfilePicture(), 'documentFile');
            }
        }
        if ($avatar) {
            $payload['context']['user']['avatar'] = $avatar;
        }
        if ($room->getServer()->getJwtModeratorPosition() == 0) {
            $this->logger->debug('We add moderator rights to the root claim');
            $payload['moderator'] = $moderator;
        } elseif ($room->getServer()->getJwtModeratorPosition() == 1) {
            $payload['context']['user']['moderator'] = $moderator;
        }
        $screen = [
            'screen-sharing' => true,
            'private-message' => true,

        ];
        if ($room->getServer()->getFeatureEnableByJWT()) {
            $this->logger->debug('The features Enabled by JWT is enabled on the server and is set here');
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

    public function generateEncryptedSecret(Server $server): ?string
    {
        if (!$server->isLiveKitServer()) {
            return null;
        }

        $encSecret = null;
        if ($server->isLiveKitServer()) {
            $this->logger->debug('Build JWT for Livekit Server', ['servername' => $server->getServerName()]);

            $cacheKey = 'livekit_public_key_' . $server->getId();
            $url = $server->getLivekitMiddlewareUrl() ?: $this->parameterBag->get('LIVEKIT_BASE_URL') . '/public.pem';

            // Fetch the public key from cache or download if not cached
            $publicKey = $this->cache->get($cacheKey, function (ItemInterface $item) use ($url) {
                // Set TTL for 1 hour
                $item->expiresAfter(3600);

                // Fetch the public key for encryption
                $response = $this->httpClient->request('GET', $url);
                if ($response->getStatusCode() !== 200) {
                    $this->logger->error('Invalid Responsecode to fetch public key for secret encryption', ['url' => $url]);
                    throw new \Exception("Unable to fetch public key from URL: $url");
                }
                $publicKey = $response->getContent();
                if ($publicKey === false) {
                    $this->logger->error('Unable to fetch public key for secret encryption', ['url' => $url]);
                    throw new \Exception("Unable to fetch public key from URL: $url");
                }

                return $publicKey;
            });

            $secret = $server->getAppSecret();

            if (!empty($publicKey)) {
                $this->logger->debug('Public KEy fetched. the secret is ow encrypted', ['public key' => $publicKey]);
                try {
                    openssl_public_encrypt($secret, $encryptedSecret, $publicKey);
                    if ($encryptedSecret === false) {
                        $this->logger->error('Encryption Faild', ['error' => openssl_error_string()]);
                        throw new \Exception("Encryption of secret failed");
                    }
                    $encSecret = base64_encode($encryptedSecret);

                } catch (\Exception $exception) {
                    $this->logger->error('There was an error encryptiong the secret', ['error' => $exception->getMessage()]);
                    $payload['livekit'] = [
                        "errror" => 'Invalid Foreign encryption key'
                    ];
                }

            }
        }
        return urlencode($encSecret);
    }
}