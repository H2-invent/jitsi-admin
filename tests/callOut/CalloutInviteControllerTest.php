<?php

namespace App\Tests\callOut;

use App\Repository\CalloutSessionRepository;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use function PHPUnit\Framework\assertEquals;

class CalloutInviteControllerTest extends WebTestCase
{
    public function testInviteSuccess(): void
    {
        $client = static::createClient();

        $userRepo = self::getContainer()->get(UserRepository::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'This is a room with Lobby']);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);
        $invite = $userRepo->findOneBy(['email' => 'ldapUser@local.de']);
        $crawler = $client->request('POST', '/room/callout/invite/' . $room->getUidReal(), ['uid' => $invite->getEmail()]);

        $this->assertResponseIsSuccessful();
        self::assertEquals(json_encode(['error' => false, 'falseEmails' => []]), $client->getResponse()->getContent());
        $calloutRepo = self::getContainer()->get(CalloutSessionRepository::class);
        self::assertEquals(1, sizeof($calloutRepo->findAll()));
        $crawler = $client->request('GET', '/room/join/b/' . $room->getId());
        assertEquals(1, $crawler->filter('.calloutsymbol ')->count());
    }



    public function testNewUser(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['uidReal' => '561ghj984ssdfdf']);

        $crawler = $client->request('POST', '/room/callout/invite/' . $room->getUidReal(), ['uid' => 'newUser@local.de']);

        $this->assertResponseIsSuccessful();
        self::assertEquals(json_encode(['error' => false, 'falseEmails' => []]), $client->getResponse()->getContent());
        $calloutRepo = self::getContainer()->get(CalloutSessionRepository::class);
        self::assertEquals(0, sizeof($calloutRepo->findAll()));
        $crawler = $client->request('GET', '/room/join/b/' . $room->getId());
        assertEquals(0, $crawler->filter('.callingUserCard ')->count());
    }

    public function testFailureNotModerator(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($user);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['uidReal' => '561ghj984ssdfdf']);

        $crawler = $client->request('POST', '/room/callout/invite/' . $room->getUidReal(), ['uid' => 'newUser@local.de']);

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }
}
