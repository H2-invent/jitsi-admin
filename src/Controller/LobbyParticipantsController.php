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
        $lobbyUser = $this->getDoctrine()->getRepository(LobbyWaitungUser::class)->findOneBy(array('user'=>$user,'room'=>$room));
        if(!$lobbyUser){
            $lobbyUser = new LobbyWaitungUser();
            $lobbyUser->setUser($user);
            $lobbyUser->setRoom($room);
            $lobbyUpdateService->publishLobby($lobbyUser);
        }
        $lobbyUser->setCreatedAt(new \DateTime());
        $em = $this->getDoctrine()->getManager();
        $em->persist($lobbyUser);
        $em->flush();

       return $this->render('lobby_participants/index.html.twig',array('room'=>$room, 'server'=>$room->getServer(),'user'=>$user));
    }
    /**
     * @Route("/lobby/renew/participants/{roomUid}/{userUid}", name="lobby_participants_renew")
     */
    public function renew(LobbyUpdateService $lobbyUpdateService,$roomUid, $userUid): Response
    {

        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('uid'=>$roomUid));
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(array('uid'=>$userUid));
        $lobbyUser = $this->getDoctrine()->getRepository(LobbyWaitungUser::class)->findOneBy(array('user'=>$user,'room'=>$room));
        if($lobbyUser){
            $lobbyUpdateService->publishLobby($lobbyUser);
            return new JsonResponse(array('error'=>false));
        }
        return new JsonResponse(array('error'=>true));
    }
    /**
     * @Route("/lobby/leave/participants/{roomUid}/{userUid}", name="lobby_participants_leave")
     */
    public function remove(LobbyUpdateService $lobbyUpdateService,$roomUid, $userUid): Response
    {
        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('uid'=>$roomUid));
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(array('uid'=>$userUid));
        $lobbyUser = $this->getDoctrine()->getRepository(LobbyWaitungUser::class)->findOneBy(array('user'=>$user,'room'=>$room));
        if($lobbyUser){
            $em = $this->getDoctrine()->getManager();
            $em->remove($lobbyUser);
            $em->flush();
            return new JsonResponse(array('error'=>false));
        }
        return new JsonResponse(array('error'=>true));
    }
}
