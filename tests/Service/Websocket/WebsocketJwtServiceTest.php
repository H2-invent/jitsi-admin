<?php

namespace App\Tests\Service\Websocket;

use App\Entity\User;
use App\Service\OnlineStatusService;
use App\Service\Websocket\WebsocketJwtService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class WebsocketJwtServiceTest extends TestCase
{
    private const SECRET = 'websocket-test-secret';

    private function createService(int $defaultOnlineStatus = 0): WebsocketJwtService
    {
        $parameterBag = new ParameterBag([
            'WEBSOCKET_SECRET' => self::SECRET,
            'LAF_DEFAULT_ONLINE_STATUS' => $defaultOnlineStatus,
        ]);

        return new WebsocketJwtService($parameterBag, new OnlineStatusService($parameterBag));
    }

    public function testCreateJwtForUserContainsExpectedClaims(): void
    {
        $user = (new User())->setUid('user-uid-1')->setOnlineStatus(3);

        $token = $this->createService()->createJwt(['room-a', 'room-b'], $user);
        $payload = (array) JWT::decode($token, new Key(self::SECRET, 'HS256'));

        self::assertSame('jitsi-admin', $payload['iss']);
        self::assertSame('jitsi-admin', $payload['aud']);
        self::assertSame('user-uid-1', $payload['sub']);
        self::assertSame(3, $payload['status']);
        self::assertSame(['room-a', 'room-b'], (array) $payload['rooms']);
        self::assertGreaterThanOrEqual(0, $payload['nbf'] - $payload['iat']);
        self::assertLessThanOrEqual(2, $payload['nbf'] - $payload['iat']);
        self::assertGreaterThanOrEqual(3 * 24 * 60 * 60, $payload['exp'] - $payload['iat']);
        self::assertLessThanOrEqual(3 * 24 * 60 * 60 + 2, $payload['exp'] - $payload['iat']);
    }

    public function testCreateJwtForAnonymousUserHasNullSubjectAndZeroStatus(): void
    {
        $token = $this->createService()->createJwt(['room-a'], null);
        $payload = (array) JWT::decode($token, new Key(self::SECRET, 'HS256'));

        self::assertNull($payload['sub']);
        self::assertSame(0, $payload['status']);
        self::assertSame(['room-a'], (array) $payload['rooms']);
    }

    public function testCreateJwtUsesDefaultStatusWhenUserHasNoOnlineStatus(): void
    {
        $user = (new User())->setUid('user-uid-2');

        $token = $this->createService(7)->createJwt([], $user);
        $payload = (array) JWT::decode($token, new Key(self::SECRET, 'HS256'));

        self::assertSame(7, $payload['status']);
    }

    public function testCreateJwtIsSignedWithConfiguredSecret(): void
    {
        $token = $this->createService()->createJwt(['room-a'], null);

        $this->expectException(SignatureInvalidException::class);
        JWT::decode($token, new Key('a-different-secret', 'HS256'));
    }
}
