<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use App\Tests\Support\LobbyRoom;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SipCallOutControllerTest extends WebTestCase
{
    public function testInviteAddsUserToLobby(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('POST', '/room/callout/invite/' . LobbyRoom::UID_REAL, ['uid' => 'test@local4.de']);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['error']);
        $this->assertSame([], $data['falseEmails']);
    }

    public function testInviteRejectsNonOrganizer(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local4.de']);
        $client->loginUser($user);

        $client->request('POST', '/room/callout/invite/' . LobbyRoom::UID_REAL, ['uid' => 'test@local2.de']);

        $this->assertResponseStatusCodeSame(404);
    }
}
