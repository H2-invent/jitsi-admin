<?php

namespace App\Controller;

use App\Helper\JitsiAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LobbyBroadcastController extends JitsiAdminController
{
    #[Route(path: '/lobby/broadcast/{roomUid}', name: 'lobby_broadcast_websocket')]
    public function broadcastWebsocket(string $roomUid, string $userUid): Response
    {
        return new JsonResponse(['error' => false]);
    }
    #[Route(path: '/lobby/participants/{wUUid}', name: 'lobby_WaitingUser_websocket')]
    public function waitinUserWebsocket(string $wUUid): Response
    {
        return new JsonResponse(['error' => false]);
    }
}
