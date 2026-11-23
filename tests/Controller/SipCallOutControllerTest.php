<?php

namespace App\Tests\Controller;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SipCallOutControllerTest extends WebTestCase
{
    public function testInviteAddsUserToLobby(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'Room Tomorrow']);
        $client->loginUser($user);

        $client->request('POST', '/room/callout/invite/' . $room->getUidReal(), ['uid' => 'test@local4.de']);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['error']);
        $this->assertSame([], $data['falseEmails']);
    }

    public function testInviteRejectsNonOrganizer(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local4.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'Room Tomorrow']);
        $client->loginUser($user);

        $client->request('POST', '/room/callout/invite/' . $room->getUidReal(), ['uid' => 'test@local2.de']);

        $this->assertResponseStatusCodeSame(404);
    }
}
