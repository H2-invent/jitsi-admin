<?php

namespace App\Tests\Sumary;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DownloadSummaryControllerTest extends WebTestCase
{
    public function testDownload(): void
    {
        $client = static::createClient();

        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $client->request('GET', 'room/download/summary?room=' . $room->getId());
        $this->assertSame('test', $client->getKernel()->getEnvironment());
    }
}
