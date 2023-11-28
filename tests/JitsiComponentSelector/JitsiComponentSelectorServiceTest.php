<?php

namespace App\Tests\JitsiComponentSelector;

use App\Service\caller\JitsiComponentSelectorService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

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
                        'callName' => 'testroom' ,
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
                ]
            ],
            $sut->buildRequestData(
                baseUrl: 'https://testurl.de',
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
                        'callName' => 'testroom?jwt=test.jwt.signature' ,
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
                ]
            ],
            $sut->buildRequestData(
                baseUrl: 'https://testurl.de',
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

    public function testFetchResult(): void
    {
        $kernel = self::bootKernel();


        $httpClientMock = $this->createMock(HttpClientInterface::class);


        // Beispiel Response
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('toArray')->willReturn(['status' => 'ROOM_ClOSED']);

        // Konfiguriere den HttpClientMock, um die Response zurückzugeben
        $httpClientMock->method('request')->willReturn($responseMock);

        $sut = self::getContainer()->get(JitsiComponentSelectorService::class);

        self::assertEquals(
            [
                'callParams' => [
                    'callUrlInfo' => [
                        'baseUrl' => 'https://testurl.de',
                        'callName' => 'testroom?jwt=test.jwt.signature' ,
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
                ]
            ],
            $sut->fetchComponentSelectorResult(
                baseUrl: 'https://testurl.de',
                roomName: 'testroom',
                displayName: 'testname',
            )
        );
    }

}
