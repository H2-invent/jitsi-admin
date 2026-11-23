<?php

namespace App\Tests\Controller;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ChangeLogControllerDeputyTest extends WebTestCase
{
    public function testIndexRendersChangeLogForModerator(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 0']);
        $client->loginUser($user);

        $client->request('GET', '/room/change/log?room_id=' . $room->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.modal-dialog');
    }

    public function testIndexRejectsNonModerator(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local2.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 0']);
        $client->loginUser($user);

        $client->request('GET', '/room/change/log?room_id=' . $room->getId());

        $this->assertResponseStatusCodeSame(404);
    }
}
