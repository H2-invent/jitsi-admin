<?php

namespace App\Tests\Controller;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SchedulerPublicCreatorControllerTest extends WebTestCase
{
    private function getSchedulingRoom(): array
    {
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'Termin finden: 0']);
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        return [$room, $user];
    }

    public function testIndexRendersScheduler(): void
    {
        $client = static::createClient();
        [$room, $user] = $this->getSchedulingRoom();

        $client->request('GET', '/scheduler/public/creator/?room_id=' . $room->getUid() . '&user_id=' . $user->getUid());

        $this->assertResponseIsSuccessful();
    }

    public function testIndexWithoutRoomNotFound(): void
    {
        $client = static::createClient();
        [, $user] = $this->getSchedulingRoom();

        $client->request('GET', '/scheduler/public/creator/?room_id=unknown&user_id=' . $user->getUid());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAddCreatesSchedulingTime(): void
    {
        $client = static::createClient();
        [$room, $user] = $this->getSchedulingRoom();

        $client->request('GET', '/scheduler/public/creator/add?room_id=' . $room->getUid() . '&user_id=' . $user->getUid() . '&date=2026-06-01T10:00');

        $this->assertResponseIsSuccessful();
        $this->assertSame(['error' => false], json_decode($client->getResponse()->getContent(), true));
    }

    public function testAddWithoutRoomNotFound(): void
    {
        $client = static::createClient();
        [, $user] = $this->getSchedulingRoom();

        $client->request('GET', '/scheduler/public/creator/add?room_id=unknown&user_id=' . $user->getUid() . '&date=2026-06-01T10:00');

        $this->assertResponseStatusCodeSame(404);
    }
}
