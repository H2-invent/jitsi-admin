<?php

namespace App\Tests\Controller;

use App\Controller\LobbyBroadcastController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

class LobbyBroadcastControllerTest extends TestCase
{
    public function testBroadcastWebsocketReturnsJson(): void
    {
        $controller = (new \ReflectionClass(LobbyBroadcastController::class))->newInstanceWithoutConstructor();

        $response = $controller->broadcastWebsocket('room-uid', 'user-uid');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(['error' => false], json_decode($response->getContent(), true));
    }

    public function testWaitinUserWebsocketReturnsJson(): void
    {
        $controller = (new \ReflectionClass(LobbyBroadcastController::class))->newInstanceWithoutConstructor();

        $response = $controller->waitinUserWebsocket('waiting-user-uid');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(['error' => false], json_decode($response->getContent(), true));
    }
}
