<?php

namespace App\Tests\Roomkit;

use App\Repository\RoomsRepository;
use App\Service\Caller\CallerPrepareService;
use App\Service\RoomService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RoomkitControllerTest extends WebTestCase
{
    public function testUnauthorized(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/roomkit/join-data/123456789/66125');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(['authorized' => false]), $client->getResponse()->getContent());

        $client->request('GET', '/api/v1/roomkit/join-data/123456789/66125', [], [], ['HTTP_authorization' => 'Bearer wrong']);
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    public function testRoomOrPinNotFound(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer roomkitTestSecret123456']);

        // unknown room number
        $client->request('GET', '/api/v1/roomkit/join-data/123456789/66125');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        // known room number, unknown PIN
        $client->request('GET', '/api/v1/roomkit/join-data/123419/0');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testJoinData(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer roomkitTestSecret123456']);

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $roomService = self::getContainer()->get(RoomService::class);

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $callerId = $room->getCallerIds()[0];

        $client->request('GET', '/api/v1/roomkit/join-data/123419/' . $callerId->getCallerId());
        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($roomService->getRoomName($room), $data['roomName']);
        $this->assertNotEmpty($data['jwt']);
        $this->assertArrayHasKey('secret', $data);
    }
}
