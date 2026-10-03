<?php

namespace App\Tests\Join;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\RoomService;
use App\UtilsHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class JoinControllerTest extends WebTestCase
{
    public function testjoinRoomBrowser(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/room/join/b/' . $room->getId());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $content = $client->getResponse()->getContent();
        preg_match("/jwt: '([^']+)'/", $content, $matches);
        self::assertNotEmpty($matches[1]);
        $decoded = JWT::decode($matches[1], new Key($room->getServer()->getAppSecret(), 'HS256'));
        self::assertEquals('123456781', $decoded->room);
        self::assertEquals(true, $decoded->moderator);
    }
    public function testjoinRoomApp(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/room/join/a/' . $room->getId());
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $jwtFactory = self::getContainer()->get(RoomService::class);
        $jwt = $jwtFactory->generateJwt($room, $testUser, 'User, Test, test@local.de');
        $slugyfy = UtilsHelper::slugify($room->getName());
        self::assertTrue($client->getResponse()->isRedirect('jitsi-meet://' . $room->getServer()->getUrl() . '/' . $room->getUid() . '?jwt=' . $jwt . '#config.subject=%22' . $slugyfy . '%22'));
    }
}
