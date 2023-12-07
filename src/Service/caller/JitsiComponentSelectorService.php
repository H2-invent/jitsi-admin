<?php

namespace App\Service\caller;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Service\RoomService;
use App\Service\ThemeService;
use Firebase\JWT\JWT;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class JitsiComponentSelectorService
{
    private ?string $baseUrl;
    private string $jsonResult;
    private $jwt;
    private $publicKey;
    private $privateKey;
    private $kid;

    public function __construct(
        private HttpClientInterface   $httpClient,
        private ThemeService          $themeService,
        private RoomService           $roomService,
        private ParameterBagInterface $parameterBag,
        private KernelInterface       $kernel)
    {
        $this->baseUrl = null;
        $dir = $this->kernel->getProjectDir();
        $privateKey = $dir . $this->parameterBag->get('JITSI_COMPONENT_SELECTOR_PRIVATE_PATH');
        $this->privateKey = file_get_contents(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $privateKey));
        $publicKey = $dir . $this->parameterBag->get('JITSI_COMPONENT_SELECTOR_PUBLIC_PATH');
        $this->publicKey = file_get_contents(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $publicKey));
        $this->kid = $this->parameterBag->get('JITSI_COMPONENT_SELECTOR_JWT_KID');
    }


    public function setHttpClient(HttpClientInterface $httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @return mixed
     */
    public function getJwt()
    {
        return $this->jwt;
    }

    public function getPublicKey(): bool|string
    {
        return $this->publicKey;
    }

    public function setBaseUrlFromServer(Server $server): void
    {
        $this->baseUrl = 'https://' . $server->getUrl() . '/jitsi-component-selector/sessions/start';
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }


    public function fetchComponentKey(Rooms $room, User $user)
    {
        if (!$this->baseUrl) {
            $this->setBaseUrlFromServer($room->getServer());;
        }

        $res = $this->fetchComponentSelectorResult(
            baseUrl: $room->getServer()->getUrl(),
            roomName: $room->getUid(),
            displayName: $user->getFormatedName($this->themeService->getApplicationProperties('laf_showNameFrontend')),
            jwt: $room->getServer()->getAppId() ? $this->roomService->generateJwt(room: $room, user: $user, userName: $user->getFormatedName($this->themeService->getApplicationProperties('laf_showNameFrontend'))) : null
        );
        if (isset($res['componentKey'])) {
            return $res['componentKey'];
        } else {
            throw new \Exception('Component Key not found');
        }
    }

    public function fetchComponentSelectorResult(
        string  $baseUrl,
        string  $roomName,
        string  $displayName,
        ?string $jwt = null,
        bool    $autoAnswer = true,
        int     $autoAnswerTime = 1000,
        string  $sipAddress = 'sip:jibri@127.0.0.1',
        string  $environment = 'default-env',
        string  $region = 'default-region',
        string  $type = 'SIP-JIBRI'
    )
    {
        $requestData = $this->buildRequestData(
            baseUrl: $baseUrl,
            roomName: $roomName,
            displayName: $displayName,
            jwt: $jwt,
            autoAnswer: $autoAnswer,
            autoAnswerTime: $autoAnswerTime,
            sipAddress: $sipAddress,
            environment: $environment,
            region: $region,
            type: $type
        );
        if (!$this->baseUrl) {
            throw new \Exception('The base Url is not Set. Set the Base URl with the Server Entity');
        }

        $response = $this->httpClient->request(method: 'POST', url: $this->baseUrl, options: [
            'json' => $requestData,
            'auth_bearer'=>$this->createAuthToken(),
        ]);
        if (200 != $response->getStatusCode()) {
            throw new \Exception('Response status code is different than expected.');
        }
        $decodedPayload = $response->toArray();
        return $decodedPayload;
    }

    public function buildRequestData(
        string  $baseUrl,
        string  $roomName,
        string  $displayName,
        ?string $jwt,
        bool    $autoAnswer,
        int     $autoAnswerTime,
        string  $sipAddress,
        string  $environment,
        string  $region,
        string  $type,
    )
    {
        $requestData = [
            'callParams' => [
                'callUrlInfo' => [
                    'baseUrl' => $baseUrl,
                    'callName' => $roomName . ($jwt ? ('?jwt=' . $jwt) : ''),
                ],
                'componentParams' => [
                    'type' => $type,
                    'region' => $region,
                    'environment' => $environment,
                ],
                'metadata' => [
                    'sipClientParams' => [
                        'sipAddress' => $sipAddress,
                        'displayName' => $displayName,
                        'autoAnswer' => $autoAnswer,
                        'autoAnswerTimer' => $autoAnswerTime
                    ]
                ]
            ]
        ];

        return $requestData;
    }

    public function createAuthToken()
    {

        $payload = [
            'iss' => 'signal',
            'aud' => 'jitsi-component-selector'
        ];
        $this->jwt = JWT::encode($payload, $this->privateKey, 'RS256', null, ['kid' => $this->kid]);
        return $this->jwt;
    }

    public function verifyToken($token): bool
    {

        try {
            JWT::decode($token, $this->publicKey, ['RS256']);
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }
}