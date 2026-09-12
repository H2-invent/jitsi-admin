<?php

namespace App\Tests\Twig;

use App\Entity\User;
use App\Service\OnlineStatusService;
use App\Service\Websocket\WebsocketJwtService;
use App\Twig\WebsocketJwt;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Twig\TwigFunction;

class WebsocketJwtTest extends TestCase
{
    private const SECRET = 'test-secret';

    private function createExtension(string $mercureUrl = 'https://mercure.example.org/.well-known/mercure'): WebsocketJwt
    {
        $parameterBag = new ParameterBag([
            'WEBSOCKET_SECRET' => self::SECRET,
            'MERCURE_PUBLIC_URL' => $mercureUrl,
            'LAF_DEFAULT_ONLINE_STATUS' => 0,
        ]);
        $onlineStatusService = new OnlineStatusService($parameterBag);

        return new WebsocketJwt(new WebsocketJwtService($parameterBag, $onlineStatusService), $parameterBag);
    }

    public function testGetFunctionsReturnsExpectedTwigFunctions(): void
    {
        $extension = $this->createExtension();

        $functions = $extension->getFunctions();
        $names = array_map(static fn(TwigFunction $function) => $function->getName(), $functions);

        $this->assertSame(['getJwtforWebsocket', 'getUrlforWebsocket'], $names);
        $this->assertSame([$extension, 'getJwtforWebsocket'], $functions[0]->getCallable());
        $this->assertSame([$extension, 'getUrlforWebsocket'], $functions[1]->getCallable());
    }

    public function testGetUrlforWebsocketReplacesHttpsWithWss(): void
    {
        $extension = $this->createExtension('https://mercure.example.org/.well-known/mercure');

        $this->assertSame('wss://mercure.example.org/.well-known/mercure', $extension->getUrlforWebsocket());
    }

    public function testGetUrlforWebsocketReplacesHttpWithWs(): void
    {
        $extension = $this->createExtension('http://mercure.example.org/.well-known/mercure');

        $this->assertSame('ws://mercure.example.org/.well-known/mercure', $extension->getUrlforWebsocket());
    }

    public function testGetJwtforWebsocketContainsExpectedPayload(): void
    {
        $extension = $this->createExtension();
        $user = (new User())->setUid('user-123')->setOnlineStatus(2);

        $token = $extension->getJwtforWebsocket(['room-1', 'room-2'], $user);
        $payload = (array) JWT::decode($token, new Key(self::SECRET, 'HS256'));

        $this->assertSame('jitsi-admin', $payload['iss']);
        $this->assertSame('jitsi-admin', $payload['aud']);
        $this->assertSame('user-123', $payload['sub']);
        $this->assertSame(2, $payload['status']);
        $this->assertSame(['room-1', 'room-2'], (array) $payload['rooms']);
        $this->assertLessThan($payload['exp'], $payload['iat']);
    }

    public function testGetJwtforWebsocketForAnonymousUser(): void
    {
        $extension = $this->createExtension();

        $token = $extension->getJwtforWebsocket(['room-1'], null);
        $payload = (array) JWT::decode($token, new Key(self::SECRET, 'HS256'));

        $this->assertNull($payload['sub']);
        $this->assertSame(0, $payload['status']);
    }
}
