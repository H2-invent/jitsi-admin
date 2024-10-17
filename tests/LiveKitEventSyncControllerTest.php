<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LiveKitEventSyncControllerTest extends WebTestCase
{
    private $roomFinished = [
        "event" => "room_finished",
        "room" => [
            "sid" => "RM_FMtSomfxpkkU",
            "name" => "test",
            "emptyTimeout" => 300,
            "departureTimeout" => 20,
            "creationTime" => "1724746166",
            "turnPassword" => "RT322JDcmfjOXmIWMrJppDfPrxEuaefBY5FJ4RTZTpwB",
            "enabledCodecs" => [
                ["mime" => "audio/opus"],
                ["mime" => "audio/red"],
                ["mime" => "video/VP8"],
                ["mime" => "video/H264"],
                ["mime" => "video/VP9"],
                ["mime" => "video/AV1"],
            ],
        ],
        "id" => "EV_S2BuXd6ZDeUh",
        "createdAt" => "1724746167",
    ];
    private $roomStarted =
        [
            "event" => "room_started",
            "room" => [
                "sid" => "RM_FMtSomfxpkkU",
                "name" => "test",
                "emptyTimeout" => 300,
                "departureTimeout" => 20,
                "creationTime" => "1724746166",
                "turnPassword" => "RT322JDcmfjOXmIWMrJppDfPrxEuaefBY5FJ4RTZTpwB",
                "enabledCodecs" => [
                    ["mime" => "audio/opus"],
                    ["mime" => "audio/red"],
                    ["mime" => "video/VP8"],
                    ["mime" => "video/H264"],
                    ["mime" => "video/VP9"],
                    ["mime" => "video/AV1"],
                ],
            ],
            "id" => "EV_nAkEA2HxggLT",
            "createdAt" => "1724746166",
        ];

    private $userJoined = [
        "event" => "participant_joined",
        "room" => [
            "sid" => "RM_FMtSomfxpkkU",
            "name" => "test",
            "emptyTimeout" => 300,
            "departureTimeout" => 20,
            "creationTime" => "1724746001",
            "turnPassword" => "iev9nWcIfzmiseKWyOqpMDmZGiKlgqe8lTfJThJM7dxB",
            "enabledCodecs" => [
                ["mime" => "audio/opus"],
                ["mime" => "audio/red"],
                ["mime" => "video/VP8"],
                ["mime" => "video/H264"],
                ["mime" => "video/VP9"],
                ["mime" => "video/AV1"],
            ],
        ],
        "participant" => [
            "sid" => "PA_ruL7zGBfZTzo",
            "identity" => "emanuel",
            "state" => "ACTIVE",
            "joinedAt" => "1724746003",
            "name" => "emanuel",
            "version" => 2,
            "permission" => [
                "canSubscribe" => true,
                "canPublish" => true,
                "canPublishData" => true,
                "canUpdateMetadata" => true,
            ],
            "region" => "eu-germany",
        ],
        "id" => "EV_P3bYNoF44UA5",
        "createdAt" => "1724746004",
    ];
    private $userLeft = [
        "event" => "participant_left",
        "room" => [
            "sid" => "RM_FMtSomfxpkkU",
            "name" => "test",
            "emptyTimeout" => 300,
            "departureTimeout" => 20,
            "creationTime" => "1724746001",
            "turnPassword" => "iev9nWcIfzmiseKWyOqpMDmZGiKlgqe8lTfJThJM7dxB",
            "enabledCodecs" => [
                ["mime" => "audio/opus"],
                ["mime" => "audio/red"],
                ["mime" => "video/VP8"],
                ["mime" => "video/H264"],
                ["mime" => "video/VP9"],
                ["mime" => "video/AV1"],
            ],
        ],
        "participant" => [
            "sid" => "PA_ruL7zGBfZTzo",
            "identity" => "emanuel",
            "state" => "DISCONNECTED",
            "joinedAt" => "1724746003",
            "name" => "emanuel",
            "version" => 16,
            "permission" => [
                "canSubscribe" => true,
                "canPublish" => true,
                "canPublishData" => true,
                "canUpdateMetadata" => true,
            ],
            "region" => "eu-germany",
            "isPublisher" => true,
        ],
        "id" => "EV_x8Er9Y8i3Ywf",
        "createdAt" => "1724746155",
    ];
    private $validToken = 'TEST_LIVEKIT_API_TOKEN';

    public function testAuthenticationSuccess(): void
    {
        $client = static::createClient([], ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->validToken]);
        $crawler = $client->request('POST', '/livekit/event', [], [], [], json_encode($this->roomStarted));

        $this->assertResponseIsSuccessful();
    }

//    public function testAuthenticationFailure(): void
//    {
//        $client = static::createClient([], ['HTTP_AUTHORIZATION' => 'Bearer invalidToken']);
//        $crawler = $client->request('POST', '/livekit/event', [], [], [], json_encode($this->roomStarted));
//
//        $this->assertEquals(401, $client->getResponse()->getStatusCode());
//    }

    public function testCreateRoom(): void
    {
        $client = static::createClient([], ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->validToken]);
        $crawler = $client->request('POST', '/livekit/event', [],[],[],json_encode($this->roomStarted));
        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), 'Invalid JSON response');
        $this->assertJsonStringEqualsJsonString('{"error": false}', $client->getResponse()->getContent());
    }

    public function testDestroyRoom(): void
    {
        $client = static::createClient([], ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->validToken]);
        $crawler = $client->jsonRequest('POST', '/livekit/event', $this->roomStarted);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), 'Invalid JSON response');
        $this->assertJsonStringEqualsJsonString('{"error": false}', $client->getResponse()->getContent());
        $crawler = $client->jsonRequest('POST', '/livekit/event', $this->roomFinished);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), 'Invalid JSON response');
        $this->assertJsonStringEqualsJsonString('{"error": false}', $client->getResponse()->getContent());
    }

    public function testJoinRoom(): void
    {
        $client = static::createClient([], ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->validToken]);
        $crawler = $client->jsonRequest('POST', '/livekit/event', $this->roomStarted);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), 'Invalid JSON response');
        $this->assertJsonStringEqualsJsonString('{"error": false}', $client->getResponse()->getContent());

        $crawler = $client->jsonRequest('POST', '/livekit/event', $this->userJoined);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), 'Invalid JSON response');
        $this->assertJsonStringEqualsJsonString('{"error": false}', $client->getResponse()->getContent());

        $crawler = $client->jsonRequest('POST', '/livekit/event', $this->userLeft);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), 'Invalid JSON response');
        $this->assertJsonStringEqualsJsonString('{"error": false}', $client->getResponse()->getContent());

        $crawler = $client->jsonRequest('POST', '/livekit/event', $this->roomFinished);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), 'Invalid JSON response');
        $this->assertJsonStringEqualsJsonString('{"error": false}', $client->getResponse()->getContent());
    }

    public function testErrorRoom(): void
    {
        $client = static::createClient([], ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->validToken]);
        $crawler = $client->jsonRequest('POST', '/livekit/event', $this->roomFinished);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), 'Invalid JSON response');
        $this->assertJsonStringEqualsJsonString('{"error":"Room Jitsi ID not found"}', $client->getResponse()->getContent());

        $crawler = $client->jsonRequest('POST', '/livekit/event', $this->userLeft);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), 'Invalid JSON response');
        $this->assertJsonStringEqualsJsonString('{"error":"Wrong occupant ID. The occupant is not in the database"}', $client->getResponse()->getContent());
        $wrongIdOfUser = $this->userJoined;
        $wrongIdOfUser['room']['sid'] = 'invalidSID';
        $crawler = $client->jsonRequest('POST', '/livekit/event', $wrongIdOfUser);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), 'Invalid JSON response');
        $this->assertJsonStringEqualsJsonString('{"error":"Room Jitsi ID not found"}', $client->getResponse()->getContent());

        $crawler = $client->jsonRequest('POST', '/livekit/event', $this->userJoined);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), 'Invalid JSON response');
        $this->assertJsonStringEqualsJsonString('{"error":"Room Jitsi ID not found"}', $client->getResponse()->getContent());
    }
}

