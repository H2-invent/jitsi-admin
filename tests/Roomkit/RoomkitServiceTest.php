<?php

namespace App\Tests\Roomkit;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Service\RoomkitService;
use App\Service\RoomService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PHPUnit\Framework\TestCase;

class RoomkitServiceTest extends TestCase
{
    private const SECRET = 'roomkitServiceTestSecret1234567890';

    private function createRoom(): Rooms
    {
        $server = new Server();
        $server->setAppSecret(self::SECRET);
        $room = new Rooms();
        $room->setServer($server);

        return $room;
    }

    public function testStripsUnusedClaimsAndSetsRoomkitDefaults(): void
    {
        $originalPayload = [
            'room' => 'abc',
            'backgroundImages' => ['a.jpg', 'b.jpg'],
            'context' => [
                'lobby' => ['chatMessages' => ['hello'], 'enabled' => true],
                'user' => [
                    'name' => 'Test User',
                    'hotstandby' => true,
                    'avatarAway' => 'away.png',
                ],
            ],
        ];

        $roomService = $this->createMock(RoomService::class);
        $roomService->expects($this->once())
            ->method('generateJwt')
            ->with($this->anything(), $this->anything(), 'Test User', $this->anything(), $this->anything(), $this->anything(), true)
            ->willReturn(JWT::encode($originalPayload, self::SECRET, 'HS256'));

        $jwt = (new RoomkitService($roomService))->generateJwt($this->createRoom(), new User(), 'Test User');

        // throws if the signature is not valid for the server's app secret
        $payload = json_decode(json_encode(JWT::decode($jwt, new Key(self::SECRET, 'HS256'))), true);

        self::assertSame('abc', $payload['room']);
        self::assertArrayNotHasKey('backgroundImages', $payload);
        self::assertArrayNotHasKey('chatMessages', $payload['context']['lobby']);
        self::assertTrue($payload['context']['lobby']['enabled']);
        self::assertArrayNotHasKey('hotstandby', $payload['context']['user']);
        self::assertArrayNotHasKey('avatarAway', $payload['context']['user']);
        self::assertSame('Test User', $payload['context']['user']['name']);
        self::assertTrue($payload['context']['user']['skipPrejoin']);
        self::assertSame(['type' => 'roomkit'], $payload['context']['user']['device']);
        self::assertSame('dark', $payload['theme']['colorScheme']);
        self::assertTrue($payload['settings']['isCameraEnabled']);
        self::assertTrue($payload['settings']['isMicrophoneEnabled']);
    }

    public function testReturnsEmptyStringWhenNoJwtIsGenerated(): void
    {
        $roomService = $this->createStub(RoomService::class);
        $roomService->method('generateJwt')->willReturn('');

        $jwt = (new RoomkitService($roomService))->generateJwt($this->createRoom(), new User(), 'Test User');

        self::assertSame('', $jwt);
    }
}
