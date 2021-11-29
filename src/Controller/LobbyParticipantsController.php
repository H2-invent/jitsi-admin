<?php

namespace App\Controller;

use App\Entity\LobbyWaitungUser;
use App\Entity\Rooms;
use App\Entity\User;
use App\Service\Lobby\DirectSendService;
use App\Service\Lobby\ToModeratorWebsocketService;
use App\Service\Lobby\ToParticipantWebsocketService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class LobbyParticipantsController extends AbstractController
{
    private $translator;
    private $toModerator;
    private $toParticipant;
    public function __construct(ToParticipantWebsocketService $toParticipantWebsocketService, ToModeratorWebsocketService $toModeratorWebsocketService,TranslatorInterface $translator, DirectSendService $lobbyUpdateService)
    {
        $this->translator = $translator;
        $this->lobbyUpdateService = $lobbyUpdateService;
        $this->toModerator = $toModeratorWebsocketService;
        $this->toParticipant = $toParticipantWebsocketService;
    }

    /**
     * @Route("/lobby/participants/{roomUid}/{userUid}", name="lobby_participants_wait")
     */
    public function index($roomUid, $userUid): Response
    {

        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('uidReal'=>$roomUid));
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(array('uid'=>$userUid));
        $lobbyUser = $this->getDoctrine()->getRepository(LobbyWaitungUser::class)->findOneBy(array('user'=>$user,'room'=>$room));

        if(!$lobbyUser){
            $lobbyUser = new LobbyWaitungUser();
            $lobbyUser->setUser($user);
            $lobbyUser->setRoom($room);
            $lobbyUser->setCreatedAt(new \DateTime());
            $lobbyUser->setUid(md5(uniqid()));
            $em = $this->getDoctrine()->getManager();
            $em->persist($lobbyUser);
            $em->flush();
            $this->toModerator->newParticipantInLobby($lobbyUser);
            $this->toModerator->refreshLobby($lobbyUser);
        }

       return $this->render('lobby_participants/index.html.twig',array('room'=>$room, 'server'=>$room->getServer(),'user'=>$user));
    }
    /**
     * @Route("/lobby/renew/participants/{roomUid}/{userUid}", name="lobby_participants_renew")
     */
    public function renew($roomUid, $userUid): Response
    {

        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('uidReal'=>$roomUid));
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(array('uid'=>$userUid));
        $lobbyUser = $this->getDoctrine()->getRepository(LobbyWaitungUser::class)->findOneBy(array('user'=>$user,'room'=>$room));
        if($lobbyUser){
            $this->toModerator->newParticipantInLobby($lobbyUser);
            return new JsonResponse(array('error'=>false,'message'=>$this->translator->trans('lobby.participant.ask.sucess'),'color'=>'success'));
        }
        return new JsonResponse(array('error'=>true,'message'=>$this->translator->trans('Fehler')));
    }
    /**
     * @Route("/lobby/leave/participants/{roomUid}/{userUid}", name="lobby_participants_leave")
     */
    public function remove($roomUid, $userUid): Response
    {
        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('uidReal'=>$roomUid));
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(array('uid'=>$userUid));
        $lobbyUser = $this->getDoctrine()->getRepository(LobbyWaitungUser::class)->findOneBy(array('user'=>$user,'room'=>$room));
        if($lobbyUser){
            $em = $this->getDoctrine()->getManager();
            $em->remove($lobbyUser);
            $em->flush();
            $this->toModerator->refreshLobby($lobbyUser);
            return new JsonResponse(array('error'=>false));
        }
        return new JsonResponse(array('error'=>true));
    }

}
