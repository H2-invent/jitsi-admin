<?php

namespace App\Tests\callOut;

use App\Repository\CallerIdRepository;
use App\Repository\CalloutSessionRepository;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CalloutApiControllerTest extends WebTestCase
{
    public function testEmptyResponse(): void
    {
        $client = static::createClient(
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer 123456',
            ]
        );
        $crawler = $client->request('GET', '/api/v1/call/out/');
        $this->assertResponseIsSuccessful();
        self::assertEquals('{"calls":[]}', $client->getResponse()->getContent());
    }
    public function testEmptyNoAuthorization(): void
    {
        $client = static::createClient(
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer invalid',
            ]
        );
        $crawler = $client->request('GET', '/api/v1/call/out/');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    public function testResponsePoolWitCallout(): void
    {
        $client = static::createClient(
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer 123456',
            ]
        );
        $userRepo = self::getContainer()->get(UserRepository::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['uidReal' => '561ghj984ssdfdf']);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);
        $invite = $userRepo->findOneBy(['email' => 'ldapUser@local.de']);
        $crawler = $client->request('POST', '/room/callout/invite/' . $room->getUidReal(), ['uid' => $invite->getEmail()]);
        $calloutRepo = self::getContainer()->get(CalloutSessionRepository::class);
        self::assertEquals(1, sizeof($calloutRepo->findAll()));
        $crawler = $client->request('GET', '/api/v1/call/out/');
        $this->assertResponseIsSuccessful();
        $res = json_decode($client->getResponse()->getContent(), true);
        $callerIdRepo = self::getContainer()->get(CallerIdRepository::class);
        $callerId = $callerIdRepo->findOneBy(['room' => $calloutRepo->findAll()[0]->getRoom(), 'user' => $calloutRepo->findAll()[0]->getUser()]);

        self::assertEquals(
            [
                'calls' => [
                    [
                        'state' => 'INITIATED',
                        'call_number' => '987654321012',
                        'sip_room_number' => '12341232',
                        'sip_pin' => $callerId->getCallerId(),
                        "display_name" => "Sie wurden von Test1, 1234, User, Test eingeladen",
                        "tag" => null,
                        "organisator" => "Test1, 1234, User, Test",
                        "title" => "This is a room with Lobby",
                        'is_video' => false,
                        "links" => [
                            "dial" => "/api/v1/call/out/dial/" . $calloutRepo->findAll()[0]->getUid()
                        ]
                    ]
                ],
            ],
            $res
        );
    }
}
