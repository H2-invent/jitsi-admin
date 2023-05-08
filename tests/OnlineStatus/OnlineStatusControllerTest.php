<?php

namespace App\Tests\OnlineStatus;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OnlineStatusControllerTest extends WebTestCase
{
    public function testgoOnline(): void
    {
        $client = static::createClient();
        $userrepo = self::getContainer()->get(UserRepository::class);
        $user = $userrepo->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);
        $crawler = $client->request('GET', '/room/online/status?status=1');
        $this->assertResponseIsSuccessful();
        self::assertEquals(json_encode(['error' => false, 'status' => 1]), $client->getResponse()->getContent());
        self::assertEquals(1, $user->getOnlineStatus());
    }

    public function testgoOfline(): void
    {
        $client = static::createClient();
        $userrepo = self::getContainer()->get(UserRepository::class);
        $user = $userrepo->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);
        $crawler = $client->request('GET', '/room/online/status?status=0');
        $this->assertResponseIsSuccessful();
        self::assertEquals(json_encode(['error' => false, 'status' => 0]), $client->getResponse()->getContent());
        self::assertEquals(0, $user->getOnlineStatus());
    }
}
