<?php

namespace App\Tests\Controller;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReportControllerTest extends WebTestCase
{
    public function testCreateRendersReportForModerator(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $client->loginUser($user);

        $client->request('GET', '/room/report/' . $room->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testCreateRejectsForeignKey(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local4.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $client->loginUser($user);

        $client->request('GET', '/room/report/' . $room->getId());

        $this->assertResponseStatusCodeSame(404);
    }
}
