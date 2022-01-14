<?php

namespace App\Controller;

use App\Entity\LobbyWaitungUser;
use App\Entity\Rooms;
use App\Entity\User;
use App\Service\Lobby\DirectSendService;
use App\Service\Lobby\ToModeratorWebsocketService;
use App\Service\Lobby\ToParticipantWebsocketService;
use PHPUnit\Util\Json;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class LobbyBroadcastController extends AbstractController
{
    private $directSendService;

    public function __construct(DirectSendService $directSendService)
    {
        $this->directSendService = $directSendService;
    }


    /**
     * @Route("/lobby/broadcast/{roomUid}", name="lobby_broadcast_websocket")
     */
    public function broadcastWebsocket($roomUid, $userUid): Response
    {
        return new JsonResponse(array('error' => false));
    }
    /**
     * @Route("/lobby/broadcast/{wUUid}", name="lobby_WaitingUser_websocket")
     */
    public function waitinUserWebsocket( $wUUid): Response
    {
        return new JsonResponse(array('error' => false));
    }

}
