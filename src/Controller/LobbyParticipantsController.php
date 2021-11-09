<?php

namespace App\Controller;

use App\Entity\LobbyWaitungUser;
use App\Entity\Rooms;
use App\Entity\User;
use App\Service\LobbyUpdateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LobbyParticipantsController extends AbstractController
{
    /**
     * @Route("/lobby/participants/{roomUid}/{userUid}", name="lobby_participants_wait")
     */
    public function index(LobbyUpdateService $lobbyUpdateService,$roomUid, $userUid): Response
    {

        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('uid'=>$roomUid));
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(array('uid'=>$userUid));
        $lobbyUser = new LobbyWaitungUser();
        $lobbyUser->setUser($user);
        $lobbyUser->setRoom($room);
        $lobbyUser->setCreatedAt(new \DateTime());
        $em = $this->getDoctrine()->getManager();
        $em->persist($lobbyUser);
        $em->flush();
        $lobbyUpdateService->publishLobby($lobbyUser,$room);
        return new JsonResponse(array('error'=>true));
    }
}
