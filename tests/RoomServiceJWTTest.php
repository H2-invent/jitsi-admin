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
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Cache\ItemInterface;

class RoomServiceJWTTest extends KernelTestCase
{


    public static function setUpBeforeClass(): void
    {
        self::$kernel = self::bootKernel();
        $container = self::$kernel->getContainer();

        $container->set('cache.app', new NullAdapter());
//        $container->set('cache.system', new NullAdapter());
        // Falls du andere Cache-Pools hast, setze diese ebenfalls auf NullAdapter
    }
    public function testGenerateJwtPayloadWithValidKey()
    {
        $validEncryptionKEy = '-----BEGIN RSA PUBLIC KEY-----
MIGJAoGBAIsxWyQsZnR9F2dOSNlRQMJNCc25LwcKy0BOUElatizt3sf+nI0p0xJ+
urlQ4YIVv+/6z+BzHaGLnS6Yx0kXxg9MI5K9myhjvaqwVTfYw8E2bywRnDPRVg7h
zWPNFyFPG1rXIvin7AnHWdl5Qmx5c7atFIHRU0Jhr/Nf4dHmNPpjAgMP//s=
-----END RSA PUBLIC KEY-----';
        $mockResponse = new MockResponse(
            $validEncryptionKEy,
            [
                'http_code' => 200,
            ]
        );
        $validPrivateKey='-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQCLMVskLGZ0fRdnTkjZUUDCTQnNuS8HCstATlBJWrYs7d7H/pyN
KdMSfrq5UOGCFb/v+s/gcx2hi50umMdJF8YPTCOSvZsoY72qsFU32MPBNm8sEZwz
0VYO4c1jzRchTxta1yL4p+wJx1nZeUJseXO2rRSB0VNCYa/zX+HR5jT6YwIDD//7
AoGABwZiuJOOdTW+cJNyVrm7LMLeY3c7kiOR2HFBdCKyEl7SaOGhFhkNL6wrzzjL
BdKt49Vbq0OD0gbF+8JPBD/GRAO6Jh5Mbn/9ogZDLFK6I2kQKUYxQVwLvRNk/+AU
Crdc55pqne1p57UX7B3SoqQ8rPQzRny+RrW+q8RElSNxyM8CQQDCRH4Y4cuy4gJF
CTnO3aarc3HEs9SZjzoKVbwJF7F3v+mBnLIuxS7Utl5WWRn4sapocMwa+4sCC0KY
NM4F+sJPAkEAt2yTuvBCgTw2DzGPrGmOhr98l7hdHcBKkK2YWPI2oNjSpBotL3Jh
jp71/iHFqQKH2yL28fT8XDhNc/u3VGHlrQJASxedce7/dacKlysRG3o0cD0+zoVI
NMmeBSLvPbHBgPtZELUjFbhvbKwnxvaHO7Qt3AUB+5TmpPhmKXmyspYqUQJAF9e3
wFZOlAKInLDHbuzUNYS9NLjSrlOY+2GrK9dLrpyNpEp9ZmHLbmg6pNCp0V7aY++Q
+c1BDMYSiUELVx674wJBAJKamTd46qrvyZQ4EyG3EbC+1P+Rv8wjUYzqoiHEdjiM
dMoTNcFyHcQayIDo7PwBfjlGu7giuYF/CUSWKtAbFD4=
-----END RSA PRIVATE KEY-----';

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


        $encryptedSecret = base64_decode($payload['livekit']['secret']);


        $decryptedSecret = '';
       openssl_private_decrypt($encryptedSecret, $decryptedSecret, $validPrivateKey);

        self::assertEquals(
           'testSecret',
            $decryptedSecret
        );
        $payload['livekit']['secret'] = $decryptedSecret;
        self::assertEquals(
            [
                'aud' => 'jitsi_admin',
                'iss' => 'testID',
                'sub' => 'testLivekit.de',
                'room' => 'testuid',
                'context' =>
                    [
                        'user' =>
                            array (
                                'name' => 'Testuser',
                            ),
                    ],
                'livekit' =>
                    [
                        'host' => 'testLivekit.de',
                        'key' => 'testID',
                        'secret' => 'testSecret',
                    ],
                'moderator' => true,
            ],
            $payload
        );

    }

    public
    function testGenerateJwtPayloadWithInvalidKey()
    {

        $validEncryptionKEy = '-----BEGIN RSA PUBLIC KEY-----
invalidKey
-----END RSA PUBLIC KEY-----';
        $mockResponse = new MockResponse(
            $validEncryptionKEy,
            [
                'http_code' => 200,
            ]
        );
        $validPrivateKey='-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQCLMVskLGZ0fRdnTkjZUUDCTQnNuS8HCstATlBJWrYs7d7H/pyN
KdMSfrq5UOGCFb/v+s/gcx2hi50umMdJF8YPTCOSvZsoY72qsFU32MPBNm8sEZwz
0VYO4c1jzRchTxta1yL4p+wJx1nZeUJseXO2rRSB0VNCYa/zX+HR5jT6YwIDD//7
AoGABwZiuJOOdTW+cJNyVrm7LMLeY3c7kiOR2HFBdCKyEl7SaOGhFhkNL6wrzzjL
BdKt49Vbq0OD0gbF+8JPBD/GRAO6Jh5Mbn/9ogZDLFK6I2kQKUYxQVwLvRNk/+AU
Crdc55pqne1p57UX7B3SoqQ8rPQzRny+RrW+q8RElSNxyM8CQQDCRH4Y4cuy4gJF
CTnO3aarc3HEs9SZjzoKVbwJF7F3v+mBnLIuxS7Utl5WWRn4sapocMwa+4sCC0KY
NM4F+sJPAkEAt2yTuvBCgTw2DzGPrGmOhr98l7hdHcBKkK2YWPI2oNjSpBotL3Jh
jp71/iHFqQKH2yL28fT8XDhNc/u3VGHlrQJASxedce7/dacKlysRG3o0cD0+zoVI
NMmeBSLvPbHBgPtZELUjFbhvbKwnxvaHO7Qt3AUB+5TmpPhmKXmyspYqUQJAF9e3
wFZOlAKInLDHbuzUNYS9NLjSrlOY+2GrK9dLrpyNpEp9ZmHLbmg6pNCp0V7aY++Q
+c1BDMYSiUELVx674wJBAJKamTd46qrvyZQ4EyG3EbC+1P+Rv8wjUYzqoiHEdjiM
dMoTNcFyHcQayIDo7PwBfjlGu7giuYF/CUSWKtAbFD4=
-----END RSA PRIVATE KEY-----';

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
                            array (
                                'name' => 'Testuser',
                            ),
                    ],
                'livekit' =>
                    [
                        "errror" => 'Invalid Foreign encryption key'
                    ],
                'moderator' => true,
            ],
            $payload
        );
    }
}
