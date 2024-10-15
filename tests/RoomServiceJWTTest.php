<?php

namespace App\Tests;

use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Entity\Server;
use App\Service\RoomService;
use Composer\Console\Application;
use PhpCsFixer\Console\Output\Progress\NullOutput;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Cache\CacheInterface;


class RoomServiceJWTTest extends KernelTestCase
{


    private CacheInterface $cache;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->cache = self::getContainer()->get('cache.app');
    }

    protected function tearDown(): void
    {
        // Cache leeren, um sicherzustellen, dass kein Caching zwischen Tests auftritt
        if ($this->cache instanceof ResetInterface) {
            $this->cache->reset();
        } elseif (method_exists($this->cache, 'clear')) {
            $this->cache->clear();
        }

        parent::tearDown();
    }
    public function testGenerateJwtPayloadWithValidKey()
    {
        $paramterBag = self::getContainer()->get(ParameterBagInterface::class);
        $validEncryptionKEy = file_get_contents($paramterBag->get('kernel.project_dir') . DIRECTORY_SEPARATOR . 'testJwt' . DIRECTORY_SEPARATOR . 'public.pem');
        $mockResponses = [
            new MockResponse(
                $validEncryptionKEy,
                [
                    'http_code' => 200,
                ]
            ),
            new MockResponse(
                $validEncryptionKEy,
                [
                    'http_code' => 200,
                ]
            )
        ];
        $validPrivateKey = file_get_contents($paramterBag->get('kernel.project_dir') . DIRECTORY_SEPARATOR . 'testJwt' . DIRECTORY_SEPARATOR . 'private.key');
        $httpClient = new MockHttpClient($mockResponses, 'http://etherpadurl.com');

        $roomService = self::getContainer()->get(RoomService::class);
        $roomService->setHttpClient($httpClient);
        $server = new Server();
        $server->setLiveKitServer(true)
            ->setUrl('testLivekit.de')
            ->setAppId('testID')
            ->setAppSecret('testSecret');
        $rooms = new Rooms();
        $rooms->setServer($server);
        $rooms->setName('testRoom');
        $rooms->setUid('testUid')
            ->setUidReal('uidReal');
        $encryptedSecret = $roomService->generateEncryptedSecret($rooms->getServer());
        $encryptedSecret = urldecode($encryptedSecret);

        $decryptedSecret = '';
        openssl_private_decrypt(base64_decode($encryptedSecret), $decryptedSecret, $validPrivateKey);

        self::assertEquals(
            'testSecret',
            $decryptedSecret
        );
        $payload = $roomService->genereateJwtPayload('Testuser', $rooms, $server, true);
        self::assertEquals(
            [
                'aud' => 'jitsi_admin',
                'iss' => 'testID',
                'sub' => 'testLivekit.de',
                'room' => 'testuid',
                'context' =>
                    [
                        'user' =>
                            array(
                                'name' => 'Testuser',
                                'identity' => $payload['context']['user']['identity']
                            ),
                    ],
                'livekit' =>
                    [
                        'host' => 'testLivekit.de',
                        'key' => 'testID',
                    ],
                'moderator' => true,
                'backgroundImages'=>[

                    [
                        'description'=>'',
                        'url' => 'https://images.pexels.com/photos/27779028/pexels-photo-27779028/free-photo-of-landschaft-natur-himmel-wasser.jpeg'

                    ],
                    [
                        'description'=>'',
                        'url' => 'https://images.pexels.com/photos/417173/pexels-photo-417173.jpeg'

                    ],
                    [
                        'description'=>'',
                        'url' => 'https://images.pexels.com/photos/1450353/pexels-photo-1450353.jpeg'

                    ]
                ],

            ],
            $payload
        );

    }
    public function testGenerateJwtPayloadWithValidKeyandBackgrouImage()
    {
        $paramterBag = self::getContainer()->get(ParameterBagInterface::class);
        $validEncryptionKEy = file_get_contents($paramterBag->get('kernel.project_dir') . DIRECTORY_SEPARATOR . 'testJwt' . DIRECTORY_SEPARATOR . 'public.pem');
        $mockResponses = [
            new MockResponse(
                $validEncryptionKEy,
                [
                    'http_code' => 200,
                ]
            ),
            new MockResponse(
                $validEncryptionKEy,
                [
                    'http_code' => 200,
                ]
            )
        ];
        $validPrivateKey = file_get_contents($paramterBag->get('kernel.project_dir') . DIRECTORY_SEPARATOR . 'testJwt' . DIRECTORY_SEPARATOR . 'private.key');
        $httpClient = new MockHttpClient($mockResponses, 'http://etherpadurl.com');

        $roomService = self::getContainer()->get(RoomService::class);
        $roomService->setHttpClient($httpClient);
        $server = new Server();
        $server->setLiveKitServer(true)
            ->setUrl('testLivekit.de')
            ->setAppId('testID')
            ->setAppSecret('testSecret');
        $server->setLivekitBackgroundImages(
            "[
    {
      \"description\":\"Im Land\",
      \"url\": \"https://testland.de\" 
    },
{
    \"description\":\"In den Bergen\",
      \"url\": \"https://testberge.de\" 
    },
{
    \"description\":\"In der Karibik\",
      \"url\":   \"https://testkaribik.de\" 
    }
]"
        );
        $rooms = new Rooms();
        $rooms->setServer($server);
        $rooms->setName('testRoom');
        $rooms->setUid('testUid')
            ->setUidReal('uidReal');
        $encryptedSecret = $roomService->generateEncryptedSecret($rooms->getServer());
        $encryptedSecret = urldecode($encryptedSecret);

        $decryptedSecret = '';
        openssl_private_decrypt(base64_decode($encryptedSecret), $decryptedSecret, $validPrivateKey);

        self::assertEquals(
            'testSecret',
            $decryptedSecret
        );
        $payload = $roomService->genereateJwtPayload('Testuser', $rooms, $server, true);
        self::assertEquals(
            [
                'aud' => 'jitsi_admin',
                'iss' => 'testID',
                'sub' => 'testLivekit.de',
                'room' => 'testuid',
                'backgroundImages'=>[
                    [
                        'description'=>'Im Land',
                        'url'=>'https://testland.de'
                    ],
                    [
                        'description'=>'In den Bergen',
                        'url'=>'https://testberge.de'
                    ],
                    [
                        'description'=>'In der Karibik',
                        'url'=>'https://testkaribik.de'
                    ]
                ],
                'context' =>
                    [
                        'user' =>
                            array(
                                'identity' => $payload['context']['user']['identity'],
                                'name' => 'Testuser',
                            ),
                    ],
                'livekit' =>
                    [
                        'host' => 'testLivekit.de',
                        'key' => 'testID',
                    ],
                'moderator' => true,
            ],
            $payload
        );

    }

    public function testGenerateJwtPayloadWithValidKeyandInvalidBackgrouImage()
    {
        $paramterBag = self::getContainer()->get(ParameterBagInterface::class);
        $validEncryptionKEy = file_get_contents($paramterBag->get('kernel.project_dir') . DIRECTORY_SEPARATOR . 'testJwt' . DIRECTORY_SEPARATOR . 'public.pem');
        $mockResponses = [
            new MockResponse(
                $validEncryptionKEy,
                [
                    'http_code' => 200,
                ]
            ),
            new MockResponse(
                $validEncryptionKEy,
                [
                    'http_code' => 200,
                ]
            )
        ];
        $validPrivateKey = file_get_contents($paramterBag->get('kernel.project_dir') . DIRECTORY_SEPARATOR . 'testJwt' . DIRECTORY_SEPARATOR . 'private.key');
        $httpClient = new MockHttpClient($mockResponses, 'http://etherpadurl.com');

        $roomService = self::getContainer()->get(RoomService::class);
        $roomService->setHttpClient($httpClient);
        $server = new Server();
        $server->setLiveKitServer(true)
            ->setUrl('testLivekit.de')
            ->setAppId('testID')
            ->setAppSecret('testSecret');
        $server->setLivekitBackgroundImages(
            "[
   invalidJsonIshere
]"
        );
        $rooms = new Rooms();
        $rooms->setServer($server);
        $rooms->setName('testRoom');
        $rooms->setUid('testUid')
            ->setUidReal('uidReal');
        $encryptedSecret = $roomService->generateEncryptedSecret($rooms->getServer());
        $encryptedSecret = urldecode($encryptedSecret);

        $decryptedSecret = '';
        openssl_private_decrypt(base64_decode($encryptedSecret), $decryptedSecret, $validPrivateKey);

        self::assertEquals(
            'testSecret',
            $decryptedSecret
        );
        $payload = $roomService->genereateJwtPayload('Testuser', $rooms, $server, true);
        self::assertEquals(
            [
                'aud' => 'jitsi_admin',
                'iss' => 'testID',
                'sub' => 'testLivekit.de',
                'room' => 'testuid',
                'context' =>
                    [
                        'user' =>
                            array(
                                'name' => 'Testuser',

                                'identity' => $payload['context']['user']['identity'],

                            ),
                    ],
                'livekit' =>
                    [
                        'host' => 'testLivekit.de',
                        'key' => 'testID',
                    ],
                'moderator' => true,
            ],
            $payload
        );

    }


    public
    function testGenerateJwtPayloadWithInvalidKey()
    {

        $invalidEncryptionKEy = '-----BEGIN RSA PUBLIC KEY-----
invalidKey
-----END RSA PUBLIC KEY-----';
        $mockResponse = new MockResponse(
            $invalidEncryptionKEy,
            [
                'http_code' => 200,
            ]
        );
        $paramterBag = self::getContainer()->get(ParameterBagInterface::class);

        $validPrivateKey = file_get_contents($paramterBag->get('kernel.project_dir') . DIRECTORY_SEPARATOR . 'testJwt' . DIRECTORY_SEPARATOR . 'd1c8dfc1830cc0985d98acb9c6606ccb191ffdeb5c2be295c446dcea80391620.key');


        $httpClient = new MockHttpClient($mockResponse, 'http://etherpadurl.com');

        $roomService = self::getContainer()->get(RoomService::class);
        $roomService->setHttpClient($httpClient);
        $server = new Server();
        $server->setLiveKitServer(true)
            ->setUrl('testLivekit.de')
            ->setAppId('testID')
            ->setAppSecret('testSecret');
        $rooms = new Rooms();
        $rooms->setServer($server);
        $rooms->setName('testRoom');
        $rooms->setUid('testUid')
            ->setUidReal('uidReal');
        $payload = $roomService->genereateJwtPayload('Testuser', $rooms, $server, true, null, null);
        // Arrange

        self::assertEquals(
            [
                'aud' => 'jitsi_admin',
                'iss' => 'testID',
                'sub' => 'testLivekit.de',
                'room' => 'testuid',
                'context' =>
                    [
                        'user' =>
                            array(
                                'name' => 'Testuser',
                                'identity' => $payload['context']['user']['identity'],
                            ),
                    ],
                'livekit' =>
                    [
                        "error" => 'Invalid Foreign encryption key'
                    ],
                'moderator' => true,

                'backgroundImages'=>[

                    [
                        'description'=>'',
                        'url' => 'https://images.pexels.com/photos/27779028/pexels-photo-27779028/free-photo-of-landschaft-natur-himmel-wasser.jpeg'

                    ],
                    [
                        'description'=>'',
                        'url' => 'https://images.pexels.com/photos/417173/pexels-photo-417173.jpeg'

                    ],
                    [
                        'description'=>'',
                        'url' => 'https://images.pexels.com/photos/1450353/pexels-photo-1450353.jpeg'

                    ]

                ],

            ],
            $payload
        );
    }
}
