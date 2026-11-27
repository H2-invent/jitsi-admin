<?php

namespace App\Tests\Controller;

use App\Repository\RoomsRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class APIRoomControllerTest extends WebTestCase
{
    private const API_HEADERS = ['HTTP_AUTHORIZATION' => 'Bearer TestApi'];

    public function testCreateRoomCreatesRoomAndReturnsUid(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $payload = [
            'email' => 'test@local.de',
            'server' => 'meet.jit.si2',
            'start' => '2026-01-01 10:00:00',
            'duration' => 60,
            'name' => 'API Created Room',
        ];

        $client->request('POST', '/api/v1/room', $payload, [], self::API_HEADERS);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['error']);
        $this->assertNotEmpty($data['uid']);

        $room = $roomRepo->findOneBy(['uidReal' => $data['uid']]);
        $this->assertNotNull($room);
        $this->assertSame('API Created Room', $room->getName());
        $this->assertSame('test@local.de', $room->getModerator()->getEmail());
    }

    public function testCreateRoomReturnsErrorForUnknownServer(): void
    {
        $client = static::createClient();
        $payload = [
            'email' => 'test@local.de',
            'server' => 'unknown.server',
            'start' => '2026-01-01 10:00:00',
            'duration' => 60,
            'name' => 'API Created Room',
        ];

        $client->request('POST', '/api/v1/room', $payload, [], self::API_HEADERS);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);
    }

    public function testEditRoomUpdatesRoom(): void
    {
        $client = static::createClient();
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 0']);
        $payload = [
            'uid' => $room->getUidReal(),
            'server' => 'meet.jit.si2',
            'start' => '2026-02-02 12:00:00',
            'duration' => 45,
            'name' => 'API Edited Room',
        ];

        $client->request('PUT', '/api/v1/room', $payload, [], self::API_HEADERS);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['error']);

        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['uidReal' => $room->getUidReal()]);
        $this->assertSame('API Edited Room', $room->getName());
        $this->assertEquals(45, $room->getDuration());
    }

    public function testEditRoomReturnsErrorWhenRoomNotFound(): void
    {
        $client = static::createClient();
        $payload = [
            'uid' => 'does-not-exist',
            'server' => 'meet.jit.si2',
            'start' => '2026-02-02 12:00:00',
            'duration' => 45,
            'name' => 'API Edited Room',
        ];

        $client->request('PUT', '/api/v1/room', $payload, [], self::API_HEADERS);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);
    }

    public function testRemoveRoomDeletesRoom(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);

        $client->request('DELETE', '/api/v1/room', ['uid' => $room->getUidReal()], [], self::API_HEADERS);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['error']);

        $room = $roomRepo->findOneBy(['uidReal' => $room->getUidReal()]);
        $this->assertNull($room->getModerator());
    }

    public function testRemoveRoomReturnsErrorWhenRoomNotFound(): void
    {
        $client = static::createClient();

        $client->request('DELETE', '/api/v1/room', ['uid' => 'does-not-exist'], [], self::API_HEADERS);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);
    }

    public function testRemoveRoomRejectsWrongApiKey(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $headers = ['HTTP_AUTHORIZATION' => 'Bearer WrongKey'];

        $client->request('DELETE', '/api/v1/room', ['uid' => $room->getUidReal()], [], $headers);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);

        $room = $roomRepo->findOneBy(['uidReal' => $room->getUidReal()]);
        $this->assertNotNull($room->getModerator());
    }

    public function testGetServersReturnsServersOfUser(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/serverInfo', ['email' => 'test@local.de']);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['error']);
        $this->assertSame('test@local.de', $data['email']);
        $this->assertContains('meet.jit.si', $data['server']);
        $this->assertContains('meet.jit.si2', $data['server']);
        $this->assertContains('meet.jit.si3', $data['server']);
    }
}
