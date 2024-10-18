<?php

namespace App\Tests\JitsiComponentSelector;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Repository\RoomsRepository;
use App\Service\caller\JitsiComponentSelectorService;
use Firebase\JWT\JWT;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use function PHPUnit\Framework\assertEquals;

class JitsiComponentSelectorServiceTest extends KernelTestCase
{
    public function testRequestData(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $sut = self::getContainer()->get(JitsiComponentSelectorService::class);
        self::assertEquals(
            [
                'callParams' => [
                    'callUrlInfo' => [
                        'baseUrl' => 'https://testurl.de',
                        'callName' => 'testroom',
                    ],
                ],
                'componentParams' => [
                    'type' => 'SIP-JIBRI',
                    'region' => 'default-region',
                    'environment' => 'default-env',
                ],
                'metadata' => [
                    'sipClientParams' => [
                        'sipAddress' => 'sip:jibri@127.0.0.1',
                        'displayName' => 'testname',
                        'autoAnswer' => true,
                        'autoAnswerTimer' => 1000
                    ]
                ]

            ],
            $sut->buildRequestData(
                baseUrl: 'testurl.de',
                roomName: 'testroom',
                displayName: 'testname',
                jwt: null,
                autoAnswer: true,
                autoAnswerTime: 1000,
                sipAddress: 'sip:jibri@127.0.0.1',
                environment: 'default-env',
                region: 'default-region',
                type: 'SIP-JIBRI'
            )
        );
    }

    public function testRequestDataWithJwt(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $sut = self::getContainer()->get(JitsiComponentSelectorService::class);
        self::assertEquals(
            [
                'callParams' => [
                    'callUrlInfo' => [
                        'baseUrl' => 'https://testurl.de',
                        'callName' => 'testroom?jwt=test.jwt.signature',
                    ],
                ],
                'componentParams' => [
                    'type' => 'SIP-JIBRI',
                    'region' => 'default-region',
                    'environment' => 'default-env',
                ],
                'metadata' => [
                    'sipClientParams' => [
                        'sipAddress' => 'sip:jibri@127.0.0.1',
                        'displayName' => 'testname',
                        'autoAnswer' => true,
                        'autoAnswerTimer' => 1000
                    ]
                ]

            ],
            $sut->buildRequestData(
                baseUrl: 'testurl.de',
                roomName: 'testroom',
                displayName: 'testname',
                jwt: 'test.jwt.signature',
                autoAnswer: true,
                autoAnswerTime: 1000,
                sipAddress: 'sip:jibri@127.0.0.1',
                environment: 'default-env',
                region: 'default-region',
                type: 'SIP-JIBRI'
            )
        );
    }

    public function testcreateBaseUrl(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $sut = self::getContainer()->get(JitsiComponentSelectorService::class);
        $server = new Server();
        $server->setUrl('myTesturl.com');
        $sut->setBaseUrlFromServer($server);
        assertEquals('https://myTesturl.com/jitsi-component-selector/sessions/start', $sut->getBaseUrl());

    }

    public function testFetchResult(): void
    {
        $kernel = self::bootKernel();


        $httpClientMock = $this->createMock(HttpClientInterface::class);


        // Beispiel Response
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('toArray')->willReturn([
            "sessionId" => "4a258446-70ff-4096-b122-da904d3bc591",
            "type" => "SIP-JIBRI",
            "environment" => "default-env",
            "region" => "default-region",
            "status" => "PENDING",
            "componentKey" => "h2invent-sip-81fae5244e014948a48-3-7ed3c0",
            "metadata" => [
                "sipUsername" => null
            ]
        ]);
        $responseMock->method('getStatusCode')->willReturn(200);
        // Konfiguriere den HttpClientMock, um die Response zurückzugeben
        $httpClientMock->method('request')->willReturn($responseMock);

        $sut = self::getContainer()->get(JitsiComponentSelectorService::class);
        $server = new Server();
        $server->setUrl('myTesturl.com');
        $sut->setBaseUrlFromServer($server);
        $sut->setHttpClient($httpClientMock);
        self::assertEquals(
            [
                "sessionId" => "4a258446-70ff-4096-b122-da904d3bc591",
                "type" => "SIP-JIBRI",
                "environment" => "default-env",
                "region" => "default-region",
                "status" => "PENDING",
                "componentKey" => "h2invent-sip-81fae5244e014948a48-3-7ed3c0",
                "metadata" => [
                    "sipUsername" => null
                ]
            ],
            $sut->fetchComponentSelectorResult(
                baseUrl: 'https://testurl.de',
                roomName: 'testroom',
                displayName: 'testname',
            )
        );
    }

    public function testFetchResultNotDefault(): void
    {
        $kernel = self::bootKernel();


        $httpClientMock = $this->createMock(HttpClientInterface::class);


        // Beispiel Response
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('toArray')->willReturn([
            "sessionId" => "4a258446-70ff-4096-b122-da904d3bc591",
            "type" => "SIP-JIBRI",
            "environment" => "my-env",
            "region" => "default-region",
            "status" => "PENDING",
            "componentKey" => "h2invent-sip-81fae5244e014948a48-3-7ed3c0",
            "metadata" => [
                "sipUsername" => null
            ]
        ]);
        $responseMock->method('getStatusCode')->willReturn(200);
        // Konfiguriere den HttpClientMock, um die Response zurückzugeben
        $httpClientMock->method('request')->willReturn($responseMock);

        $sut = self::getContainer()->get(JitsiComponentSelectorService::class);
        $sut->setHttpClient($httpClientMock);
        $server = new Server();
        $server->setUrl('myTesturl.com');
        $sut->setBaseUrlFromServer($server);
        self::assertEquals(
            [
                "sessionId" => "4a258446-70ff-4096-b122-da904d3bc591",
                "type" => "SIP-JIBRI",
                "environment" => "my-env",
                "region" => "default-region",
                "status" => "PENDING",
                "componentKey" => "h2invent-sip-81fae5244e014948a48-3-7ed3c0",
                "metadata" => [
                    "sipUsername" => null
                ]
            ],
            $sut->fetchComponentSelectorResult(
                baseUrl: 'https://testurl.de',
                roomName: 'testroom',
                displayName: 'testname',
                environment: 'my-env'
            )
        );
    }

    public function testComponentKey(): void
    {
        $kernel = self::bootKernel();


        $httpClientMock = $this->createMock(HttpClientInterface::class);


        // Beispiel Response
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('toArray')->willReturn(
            [
                "sessionId" => "4a258446-70ff-4096-b122-da904d3bc591",
                "type" => "SIP-JIBRI",
                "environment" => "my-env",
                "region" => "default-region",
                "status" => "PENDING",
                "componentKey" => "h2invent-sip-81fae5244e014948a48-3-7ed3c0",
                "metadata" => [
                    "sipUsername" => null
                ]
            ]
        );
        $responseMock->method('getStatusCode')->willReturn(200);
        // Konfiguriere den HttpClientMock, um die Response zurückzugeben
        $httpClientMock->method('request')->willReturn($responseMock);

        $sut = self::getContainer()->get(JitsiComponentSelectorService::class);
        $sut->setHttpClient($httpClientMock);

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = new Rooms();
        $room->setName('Test room')
            ->setUid('test123');
        $server = new Server();
        $server->setUrl('testurl.de')
            ->setAppId('testId')
            ->setAppSecret('mySecret');
        $room->setServer($server);

        $user = new User();
        $user->setFirstName('Test')
            ->setLastName('User');


        self::assertEquals(
            'h2invent-sip-81fae5244e014948a48-3-7ed3c0',
            $sut->fetchComponentKey(
                room: $room,
                user: $user
            )
        );
        assertEquals('https://testurl.de/jitsi-component-selector/sessions/start', $sut->getBaseUrl());
    }

    public function testcreateJwt(): void
    {
        $kernel = self::bootKernel();
        $sut = self::getContainer()->get(JitsiComponentSelectorService::class);
        $token = $sut->createAuthToken();
        assertEquals('eyJ0eXAiOiJKV1QiLCJraWQiOiJqaXRzaS9zaWduYWwiLCJhbGciOiJSUzI1NiJ9.eyJpc3MiOiJzaWduYWwiLCJhdWQiOiJqaXRzaS1jb21wb25lbnQtc2VsZWN0b3IifQ.cGIGR-xID1xr2Bsv-akYOWMmhLsPNoWK8sPvbOyrMciJttgFd3YlaeeT6n5-30V0NiVHxW7gkTpIL3UOpxuG6iSsCRV3Djh9irUbLeVdfqcp0vy52QA6l1pJc7fQ0AUR2u3ps-q_hFr_haxocpFGQB8EU-cADKe_H4CUyqH91ThkIlel1o8mFOWkXFRby73qEX-JfAyrYq4Bw1CFViNL-y9MOf3-T8ypeQpwOzaH8HQBazGBpdz-nzGjfvX7Eynv-7RorxCE82Dph_aS4_gNPoQkbMwKQVCwSMnfZL85ihFYt37I56W9LffYNNc3iG00V61gIl0HjqEth7tX__yQky7hNyeorXxEHY1jMEKFJ7WjLtN-z9-SSkvk2gLQLeAB6eHlMq9u39L5WPtKrBfkqYeRjdwXmC0707MBLv_wkhgK2oYTY0EfmUljOKn8BepqtPoEsgXxhQMeQ02lOb5o8Hv7-igyzjvB1_GX_4SVbxOgGnmQeyjE44Mye_LgH7LX-fVvuTtGiZ_SU602KXJf391VZR-ZdXTB5UKEvwjzeSEeGHyVVRCB9J815uezCgjVvi0nWjpVOwNjJ2Ws41OjsDO7wxktJctRaKjqFVV8qUMevsHb-CvS0AUyXDnJKmmDeCB9kzI-GE1PTRjIb8mM8_pW2Riedbxu5-KPq2W2JCw', $token);
        self::assertTrue($sut->verifyToken($token));
    }

}
