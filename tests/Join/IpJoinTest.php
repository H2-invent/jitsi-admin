<?php

namespace App\Tests\Join;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class IpJoinTest extends WebTestCase
{
    public function testToManyInConference(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $room->setMaxUser(0);
        $manager->persist($room);
        $manager->flush();

        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/room/join/b/' . $room->getId());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        self::assertSelectorTextContains('.joinPageHeader','Zu viele Teilnehmenden');
    }
    public function testBlockedIp(): void
    {
        $client = static::createClient([], ['REMOTE_ADDR' => '22.22.22.22']);
        $userRepository = static::getContainer()->get(UserRepository::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $server = $room->getServer();
        $server->setAllowIp('11.11.11.11');
        $manager->persist($server);
        $manager->flush();

        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/room/join/b/' . $room->getId());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        self::assertSelectorTextContains('.joinPageHeader','Zugriff nicht erlaubt');
    }
    public function testAllowedIp(): void
    {
        $client = static::createClient([], ['REMOTE_ADDR' => '11.11.11.11']);
        $userRepository = static::getContainer()->get(UserRepository::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $server = $room->getServer();
        $server->setAllowIp('11.11.11.11');
        $manager->persist($server);
        $manager->flush();

        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/room/join/b/' . $room->getId());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        // Verify the JWT payload in the response
        preg_match("/jwt: '([^']+)'/", $content, $matches);
        self::assertNotEmpty($matches[1]);
        $decoded = JWT::decode($matches[1], new Key($server->getAppSecret(), 'HS256'));
        self::assertEquals('123456781', $decoded->room);
        self::assertEquals(true, $decoded->moderator);
    }

    public function testEnoughSpaceInConference(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $room->setMaxUser(1);
        $manager->persist($room);
        $manager->flush();

        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/room/join/b/' . $room->getId());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        preg_match("/jwt: '([^']+)'/", $content, $matches);
        self::assertNotEmpty($matches[1]);
        $decoded = JWT::decode($matches[1], new Key($room->getServer()->getAppSecret(), 'HS256'));
        self::assertEquals('123456781', $decoded->room);
    }
}
