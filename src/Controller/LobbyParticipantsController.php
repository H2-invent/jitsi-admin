<?php

namespace App\Controller;

use App\Entity\LobbyWaitungUser;
use App\Entity\Rooms;
use App\Entity\User;
use App\Service\Lobby\CreateLobbyUserService;
use App\Service\Lobby\DirectSendService;
use App\Service\Lobby\ToModeratorWebsocketService;
use App\Service\Lobby\ToParticipantWebsocketService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;


class LobbyParticipantsController extends AbstractController
{
    private $translator;
    private $toModerator;
    private $toParticipant;
    private $parameterBag;
    private $createLobbyUserService;
    public function __construct(CreateLobbyUserService $createLobbyUserService, ToParticipantWebsocketService $toParticipantWebsocketService, ToModeratorWebsocketService $toModeratorWebsocketService,TranslatorInterface $translator, DirectSendService $lobbyUpdateService, ParameterBagInterface $parameterBag)
    {
        $this->translator = $translator;
        $this->lobbyUpdateService = $lobbyUpdateService;
        $this->toModerator = $toModeratorWebsocketService;
        $this->toParticipant = $toParticipantWebsocketService;
        $this->parameterBag = $parameterBag;
        $this->createLobbyUserService = $createLobbyUserService;
    }

    /**
     * @Route("/lobby/participants/{type}/{roomUid}/{userUid}", name="lobby_participants_wait", defaults={"type" = "a"})
     */
    public function index($roomUid, $userUid,$type): Response
    {

        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('uidReal'=>$roomUid));
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(array('uid'=>$userUid));
       $lobbyUser =  $this->createLobbyUserService->createNewLobbyUser($user,$room,$type);

       return $this->render('lobby_participants/index.html.twig',array('type'=>$type,'room'=>$room, 'server'=>$room->getServer(),'user'=>$lobbyUser));
    }
    /**
     * @Route("/lobby/renew/participants/{userUid}", name="lobby_participants_renew")
     */
    public function renew($userUid): Response
    {
        $lobbyUser = $this->getDoctrine()->getRepository(LobbyWaitungUser::class)->findOneBy(array('uid'=>$userUid));
        if($lobbyUser){
            $this->toModerator->newParticipantInLobby($lobbyUser);
            $this->toModerator->refreshLobby($lobbyUser);
            return new JsonResponse(array('error'=>false,'message'=>$this->translator->trans('lobby.participant.ask.sucess'),'color'=>'success'));
        }
        return new JsonResponse(array('error'=>true,'message'=>$this->translator->trans('Fehler')));
    }
    /**
     * @Route("/lobby/leave/participants/{userUid}", name="lobby_participants_leave")
     */
    public function remove( $userUid): Response
    {

        $lobbyUser = $this->getDoctrine()->getRepository(LobbyWaitungUser::class)->findOneBy(array('uid'=>$userUid));
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
