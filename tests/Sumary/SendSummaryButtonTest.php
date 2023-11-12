<?php

namespace App\Tests\Sumary;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SendSummaryButtonTest extends WebTestCase
{
    public function testSomething(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $client->request('GET', '/room/send/summary/' . $room->getId());
        self::assertResponseRedirects('/room/dashboard',302);
        self::assertEmailCount(3);
        $email = $this->getMailerMessage();
        self::assertEmailHtmlBodyContains($email,'Konferenz Abgeschlossen');
        self::assertEmailAttachmentCount($email, 1);
    }
}
