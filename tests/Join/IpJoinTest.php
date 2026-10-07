<?php

namespace App\Tests\Join;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
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
        self::assertStringContainsString('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzNDU2NzgxIiwiY29udGV4dCI6eyJyb29tIjp7Im5hbWUiOiJUZXN0TWVldGluZzogMSIsImlzRTJFRUVuYWJsZWQiOmZhbHNlfSwidXNlciI6eyJuYW1lIjoiVXNlciwgVGVzdCwgdGVzdEBsb2NhbC5kZSIsImxhbmd1YWdlIjoiZGUiLCJ0aW1lem9uZSI6IkV1cm9wZS9CZXJsaW4ifX0sIm1vZGVyYXRvciI6dHJ1ZSwibG9iYnlNb2RlcmF0b3IiOnRydWUsInRoZW1lIjp7ImNvbG9yU2NoZW1lIjoibGlnaHQifX0.DJWpfs5KQiT-3Emb6yvrd16N6zZ3WXkwjibu3gmwP1g', $client->getResponse()->getContent());
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
        self::assertStringContainsString('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzNDU2NzgxIiwiY29udGV4dCI6eyJyb29tIjp7Im5hbWUiOiJUZXN0TWVldGluZzogMSIsImlzRTJFRUVuYWJsZWQiOmZhbHNlfSwidXNlciI6eyJuYW1lIjoiVXNlciwgVGVzdCwgdGVzdEBsb2NhbC5kZSIsImxhbmd1YWdlIjoiZGUiLCJ0aW1lem9uZSI6IkV1cm9wZS9CZXJsaW4ifX0sIm1vZGVyYXRvciI6dHJ1ZSwibG9iYnlNb2RlcmF0b3IiOnRydWUsInRoZW1lIjp7ImNvbG9yU2NoZW1lIjoibGlnaHQifX0.DJWpfs5KQiT-3Emb6yvrd16N6zZ3WXkwjibu3gmwP1g', $client->getResponse()->getContent());
    }
}
