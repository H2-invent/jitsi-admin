<?php

namespace App\Tests\Dashboard;

use App\Repository\RoomsRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ParticipantsModalApiTest extends WebTestCase
{
    public function testParticipantsModalData(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertNotNull($room);
        self::assertEquals(3, $room->getUser()->count());
        $client->loginUser($room->getModerator());

        $client->request('GET', '/room/dashboard/api/participants/' . $room->getId());
        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertSame($room->getId(), $data['roomId'] ?? null);
    }

    public function testParticipantsModalDataStructure(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertNotNull($room);
        $client->loginUser($room->getModerator());

        $client->request('GET', '/room/dashboard/api/participants/' . $room->getId());
        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertArrayHasKey('title', $data);
        self::assertArrayHasKey('addUrl', $data);
        self::assertArrayHasKey('searchUrl', $data);
        self::assertArrayHasKey('organizer', $data);
        self::assertArrayHasKey('participants', $data);
        self::assertArrayHasKey('waitinglist', $data);
        self::assertArrayHasKey('translations', $data);
        self::assertIsArray($data['organizer']);
        // The fixture room has a moderator plus two regular invitees.
        self::assertCount(2, $data['participants']);
        foreach ($data['participants'] as $participant) {
            self::assertArrayHasKey('id', $participant);
            self::assertArrayHasKey('name', $participant);
            self::assertArrayHasKey('profilePicture', $participant);
            self::assertArrayHasKey('permissions', $participant);
            self::assertArrayHasKey('actions', $participant);
        }
    }

    public function testParticipantsModalDeniedForRegularParticipant(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertNotNull($room);
        $client->loginUser($room->getUser()[1]);

        $client->request('GET', '/room/dashboard/api/participants/' . $room->getId());
        self::assertResponseStatusCodeSame(403);
    }

    public function testBulkInviteJson(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertNotNull($room);
        $client->loginUser($room->getModerator());

        $client->request(
            'POST',
            '/room/participant/add_bulk/' . $room->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode(['member' => "test@local4.de\ntestNeu@local.de"], JSON_THROW_ON_ERROR)
        );
        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertFalse($data['error']);

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(5, $room->getUser()->count());
    }

    public function testBulkInviteJsonInvalidMember(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertNotNull($room);
        $client->loginUser($room->getModerator());

        $client->request(
            'POST',
            '/room/participant/add_bulk/' . $room->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode(['member' => 'falschTeilnehmer'], JSON_THROW_ON_ERROR)
        );
        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertFalse($data['error']);
        self::assertArrayHasKey('message', $data);

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
    }
}
