<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Helper\JitsiAdminController;
use App\Service\Lobby\SendMessageToWaitingUser;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Util\Json;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/room/lobby/message', name: 'lobby_send_message')]
class SendMessageToLobbyWaitingUserController extends JitsiAdminController
{


    #[Route('/send', name: '_to_waitinguser', methods: 'POST')]
    public function index(SendMessageToWaitingUser $sendMessageToWaitingUser, Request $request): Response
    {
        $data = json_decode($request->getContent(),true);
        $res = $sendMessageToWaitingUser->sendMessage($data['uid'], $data['message'], $this->getUser());
        return new JsonResponse(array('error' => !$res));
    }

    #[Route('/send/all', name: '_to_waitinguser_all', methods: 'POST')]
    public function sendToAll(SendMessageToWaitingUser $sendMessageToWaitingUser, Request $request): Response
    {
        $data = json_decode($request->getContent(),true);
        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(array('uidReal'=>$data['uid']));
        if (!$room){
            return new JsonResponse(array('error'=>true));
        }
        $res= $sendMessageToWaitingUser->sendMessageToAllWaitingUser($data['message'], $this->getUser(),$room);
        return new JsonResponse(array('error'=>false, 'counts' => $res));
    }

}
