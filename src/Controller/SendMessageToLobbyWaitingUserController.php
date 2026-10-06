<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Helper\JitsiAdminController;
use App\Service\Lobby\SendMessageToWaitingUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[\Symfony\Component\Routing\Attribute\Route('/room/lobby/message', name: 'lobby_send_message')]
class SendMessageToLobbyWaitingUserController extends JitsiAdminController
{
    #[\Symfony\Component\Routing\Attribute\Route('/send', name: '_to_waitinguser', methods: 'POST')]
    public function index(SendMessageToWaitingUser $sendMessageToWaitingUser, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $res  = $sendMessageToWaitingUser->sendMessage($data['uid'], $data['message'], $this->getUser());

        return new JsonResponse(
            ['error' => !$res, 'message' => !$res ? $this->translator->trans('lobby.message.failed') : $this->translator->trans('lobby.message.success')]
        );
    }

    #[\Symfony\Component\Routing\Attribute\Route('/send/all', name: '_to_waitinguser_all', methods: 'POST')]
    public function sendToAll(SendMessageToWaitingUser $sendMessageToWaitingUser, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(['uidReal' => $data['uid']]);
        if (!$room) {
            return new JsonResponse(['error' => true, 'message' => $this->translator->trans('lobby.message.failed')]);
        }

        $res = $sendMessageToWaitingUser->sendMessageToAllWaitingUser($data['message'], $this->getUser(), $room);

        return new JsonResponse(
            [
                'error'   => !$res['success'],
                'counts'  => $res['counter'],
                'message' => !$res['success'] ? $this->translator->trans('lobby.message.failed') : $this->translator->trans('lobby.message.success')
            ]
        );
    }
}
