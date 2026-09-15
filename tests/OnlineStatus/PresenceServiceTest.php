<?php

namespace App\Tests\OnlineStatus;

use App\Entity\User;
use App\Service\OnlineStatus\PresenceService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class PresenceServiceTest extends TestCase
{
    private function createService(
        MockHttpClient $client,
        string $websocketInternalUrl = '',
        string $mercureUrl = 'http://websocket-ja:3000/.well-known/mercure'
    ): PresenceService {
        return new PresenceService(
            $client,
            new ParameterBag([
                'WEBSOCKET_SECRET' => 'secret',
                'WEBSOCKET_INTERNAL_URL' => $websocketInternalUrl,
                'MERCURE_URL' => $mercureUrl,
            ]),
            new NullLogger()
        );
    }

    private function createUser(?string $uid = 'uid-1'): User
    {
        $user = new User();
        $user->setUid($uid);

        return $user;
    }

    public function testOnlineStatusesAreConsideredOnline(): void
    {
        foreach (['online', 'away', 'inMeeting'] as $status) {
            $client = new MockHttpClient(
                new MockResponse(json_encode(['uid' => 'uid-1', 'status' => $status]), ['http_code' => 200])
            );
            self::assertTrue($this->createService($client)->isUserOnline($this->createUser()), $status . ' should be online');
        }
    }

    public function testOfflineStatusIsConsideredOffline(): void
    {
        $client = new MockHttpClient(
            new MockResponse(json_encode(['uid' => 'uid-1', 'status' => 'offline']), ['http_code' => 200])
        );

        self::assertFalse($this->createService($client)->isUserOnline($this->createUser()));
    }

    public function testNonOkResponseReturnsUnknown(): void
    {
        $client = new MockHttpClient(new MockResponse('', ['http_code' => 404]));

        self::assertNull($this->createService($client)->isUserOnline($this->createUser()));
    }

    public function testTransportErrorReturnsUnknown(): void
    {
        $client = new MockHttpClient(static function (): MockResponse {
            return new MockResponse('', ['error' => 'connection refused']);
        });

        self::assertNull($this->createService($client)->isUserOnline($this->createUser()));
    }

    public function testMissingUidReturnsUnknown(): void
    {
        $client = new MockHttpClient(new MockResponse('{}', ['http_code' => 200]));

        self::assertNull($this->createService($client)->isUserOnline($this->createUser(null)));
    }

    public function testBaseUrlIsDerivedFromMercureUrl(): void
    {
        $requestedUrl = null;
        $client = new MockHttpClient(function (string $method, string $url) use (&$requestedUrl): MockResponse {
            $requestedUrl = $url;

            return new MockResponse(json_encode(['status' => 'online']), ['http_code' => 200]);
        });

        $this->createService($client)->getStatusForUid('uid-1');

        self::assertEquals('http://websocket-ja:3000/presence/uid-1', $requestedUrl);
    }

    public function testExplicitInternalUrlOverridesMercureUrl(): void
    {
        $requestedUrl = null;
        $client = new MockHttpClient(function (string $method, string $url) use (&$requestedUrl): MockResponse {
            $requestedUrl = $url;

            return new MockResponse(json_encode(['status' => 'online']), ['http_code' => 200]);
        });

        $this->createService($client, 'http://websocket:3000')->getStatusForUid('uid-1');

        self::assertEquals('http://websocket:3000/presence/uid-1', $requestedUrl);
    }

    public function testAuthorizationHeaderUsesWebsocketSecret(): void
    {
        $requestedHeaders = null;
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$requestedHeaders): MockResponse {
            $requestedHeaders = $options['headers'] ?? [];

            return new MockResponse(json_encode(['status' => 'online']), ['http_code' => 200]);
        });

        $this->createService($client)->getStatusForUid('uid-1');

        self::assertContains('Authorization: Bearer secret', $requestedHeaders);
    }
}
