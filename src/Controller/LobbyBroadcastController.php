<?php

namespace App\Controller;

use App\Helper\JitsiAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class LobbyBroadcastController extends JitsiAdminController
{
    #[\Symfony\Component\Routing\Attribute\Route(path: '/lobby/broadcast/{roomUid}', name: 'lobby_broadcast_websocket')]
    public function broadcastWebsocket(string $roomUid, string $userUid): Response
    {
        return new JsonResponse(['error' => false]);
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: '/lobby/participants/{wUUid}', name: 'lobby_WaitingUser_websocket')]
    public function waitinUserWebsocket(string $wUUid): Response
    {
        return new JsonResponse(['error' => false]);
    }
}
