<?php

namespace App\Controller;

use App\Entity\LobbyWaitungUser;
use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Entity\User;
use App\Service\JoinUrlGeneratorService;
use App\Service\Lobby\DirectSendService;
use App\Service\RoomService;
use App\Service\Lobby\ToModeratorWebsocketService;
use App\Service\Lobby\ToParticipantWebsocketService;
use PHPUnit\Util\Json;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class LobbyModeratorController extends AbstractController
{
    private $parameterBag;
    private $translator;
    private $logger;
    private $toModerator;
    private $toParticipant;

    public function __construct(ToParticipantWebsocketService $toParticipantWebsocketService, ToModeratorWebsocketService $toModeratorWebsocketService, ParameterBagInterface $parameterBag, TranslatorInterface $translator, LoggerInterface $logger)
    {
        $this->parameterBag = $parameterBag;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->toModerator = $toModeratorWebsocketService;
        $this->toParticipant = $toParticipantWebsocketService;
    }



    /**
     * @Route("/room/lobby/moderator/{type}/{uid}", name="lobby_moderator", defaults={"type" = "a"})
     */
    public function index(Request $request, $uid,$type): Response
    {
        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('uidReal' => $uid));
        if ($room->getModerator() !== $this->getUser()) {
            $this->logger->log('error', 'User trys to enter room which he is no moderator of', array('room' => $room->getId(), 'user' => $this->getUser()->getUserIdentifier()));
            return $this->redirectToRoute('dashboard', array('snack' => $this->translator->trans('Fehler'), 'color' => 'danger'));
        }
        return $this->render('lobby/index.html.twig', [
            'room' => $room,
            'server' => $room->getServer(),
            'type'=>$type
        ]);
    }

    /**
     * @Route("/room/lobby/start/moderator/{t}/{room}", name="lobby_moderator_start")
     */
    public function startMeeting( $room, $t, RoomService $roomService): Response
    {
        $roomL = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('uidReal' => $room));
        if ($roomL->getModerator() !== $this->getUser()) {
            $this->logger->log('error', 'User trys to enter room which he is no moderator of', array('room' => $roomL->getId(), 'user' => $this->getUser()->getUserIdentifier()));
            return $this->redirectToRoute('dashboard', array('snack' => $this->translator->trans('Fehler'), 'color' => 'danger'));
        }
        $url = $roomService->join($roomL, $this->getUser(), $t, $this->getUser()->getFormatedName($this->parameterBag->get('laf_showNameInConference')));
        return $this->redirect($url);
    }

    /**
     * @Route("/room/lobby/accept/{wUid}", name="lobby_moderator_accept")
     */
    public function accept(Request $request, $wUid): Response
    {
        $lobbyUser = $this->getDoctrine()->getRepository(LobbyWaitungUser::class)->findOneBy(array('uid'=>$wUid));
        if(!$lobbyUser){
            return new JsonResponse(array('error' => false, 'message' => $this->translator->trans('lobby.moderator.accept.error'), 'color' => 'danger'));
        }
        $room = $lobbyUser->getRoom();
        if ($room->getModerator() !== $this->getUser()) {
            $this->logger->log('error', 'User trys to enter room which he is no moderator of', array('room' => $room->getId(), 'user' => $this->getUser()->getUserIdentifier()));
            return new JsonResponse(array('error' => false, 'message' => $this->translator->trans('lobby.moderator.accept.error'), 'color' => 'danger'));
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($lobbyUser);
        $em->flush();
        $this->toParticipant->acceptLobbyUser($lobbyUser);
        $this->toModerator->refreshLobby($lobbyUser);
        return new JsonResponse(array('error' => false, 'message' => $this->translator->trans('lobby.moderator.accept.success'), 'color' => 'success'));
    }
    /**
     * @Route("/room/lobby/acceptAll/{roomId}", name="lobby_moderator_accept_all")
     */
    public function acceptAll(Request $request, $roomId): Response
    {
        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('uidReal'=>$roomId));
        if ($room->getModerator() !== $this->getUser()) {
            $this->logger->log('error', 'User trys to enter room which he is no moderator of', array('room' => $room->getId(), 'user' => $this->getUser()->getUserIdentifier()));
            return new JsonResponse(array('error' => false, 'message' => $this->translator->trans('lobby.moderator.accept.error'), 'color' => 'danger'));
        }
        $lobbyUser = $room->getLobbyWaitungUsers();
        $em = $this->getDoctrine()->getManager();
        foreach ($lobbyUser as $data){
            $em->remove($data);
            $em->flush();
            $this->toParticipant->acceptLobbyUser($data);
            $this->toModerator->refreshLobby($data);
        }

        return new JsonResponse(array('error' => false, 'message' => $this->translator->trans('lobby.moderator.accept.all.success'), 'color' => 'success'));
    }
    /**
     * @Route("/room/lobby/decline/{wUid}", name="lobby_moderator_decline")
     */
    public function decline( $wUid): Response
    {
        $lobbyUser = $this->getDoctrine()->getRepository(LobbyWaitungUser::class)->findOneBy(array('uid'=>$wUid));
        if(!$lobbyUser){
            return new JsonResponse(array('error' => false, 'message' => $this->translator->trans('lobby.moderator.accept.error'), 'color' => 'danger'));
        }
        $room = $lobbyUser->getRoom();
        if ($room->getModerator() !== $this->getUser()) {
            $this->logger->log('error', 'User trys to enter room which he is no moderator of', array('room' => $room->getId(), 'user' => $this->getUser()->getUserIdentifier()));
            return new JsonResponse(array('error' => false, 'message' => $this->translator->trans('lobby.moderator.accept.error'), 'color' => 'danger'));
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($lobbyUser);
        $em->flush();
        $this->toParticipant->sendDecline($lobbyUser);
        $this->toModerator->refreshLobby($lobbyUser);
        return new JsonResponse(array('error' => false, 'message' => $this->translator->trans('lobby.moderator.decline.success'), 'color' => 'success'));
    }
}