<?php

namespace App\Tests\Join;

use App\Repository\RoomsRepository;
use App\Service\JoinService;
use App\Service\RoomService;
use App\UtilsHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class JoinServiceTest extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findAll()[0];
        $joinService = $this->getContainer()->get(JoinService::class);
        $room->setOnlyRegisteredUsers(true);
        self::assertEquals(true, $joinService->onlyWithUserAccount($room));
        $room->setOnlyRegisteredUsers(false);
        self::assertEquals(false, $joinService->onlyWithUserAccount($room));
        self::assertEquals(false, $joinService->onlyWithUserAccount(null));
    }

    public function testJwtToken(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $roomService = $this->getContainer()->get(RoomService::class);
        $appSecret = $room->getServer()->getAppSecret();

        // Verify JWT payload contents (signature varies with appSecret)
        $jwt = $roomService->generateJwt($room, null, 'Test User');
        $decoded = JWT::decode($jwt, new Key($appSecret, 'HS256'));
        self::assertEquals('jitsi_admin', $decoded->aud);
        self::assertEquals('jitsiId', $decoded->iss);
        self::assertEquals('meet.jit.si2', $decoded->sub);
        self::assertEquals('123456781', $decoded->room);
        self::assertEquals('Test User', $decoded->context->user->name);
        self::assertEquals(false, $decoded->moderator);

        // Verify join URLs contain valid JWT
        $res = $roomService->join($room, $room->getModerator(), 'a', 'Test User');
        $slugyfy = UtilsHelper::slugify($room->getName());
        preg_match('/jwt=([^#]+)/', $res, $matches);
        $urlJwt = $matches[1];
        self::assertNotEmpty($urlJwt);
        $urlDecoded = JWT::decode($urlJwt, new Key($appSecret, 'HS256'));
        self::assertEquals(true, $urlDecoded->moderator);
        self::assertStringContainsString($slugyfy, $res);

        $res = $roomService->join($room, $room->getModerator(), 'b', 'Test User');
        preg_match('/jwt=([^#]+)/', $res, $matches);
        $urlJwt2 = $matches[1];
        self::assertNotEmpty($urlJwt2);
        $urlDecoded2 = JWT::decode($urlJwt2, new Key($appSecret, 'HS256'));
        self::assertEquals(true, $urlDecoded2->moderator);
        self::assertStringContainsString($slugyfy, $res);
        self::assertStringStartsWith('https://', $res);
    }
}
