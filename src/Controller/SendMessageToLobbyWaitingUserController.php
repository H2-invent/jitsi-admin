<?php

namespace App\Controller;

use App\Helper\JitsiAdminController;
use App\Service\Lobby\SendMessageToWaitingUser;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class SendMessageToLobbyWaitingUserController extends JitsiAdminController
{


    #[Route('/room/lobby/message/send', name: 'lobby_send_message_to_waitinguser', methods: 'POST')]
    public function index(SendMessageToWaitingUser $sendMessageToWaitingUser, Request $request): Response
    {
        $data = json_decode($request->getContent(),true);
        dump($data);
        $res = $sendMessageToWaitingUser->sendMessage($data['uid'], $data['message'], $this->getUser());
        return new JsonResponse(array('error' => !$res));
    }
}
