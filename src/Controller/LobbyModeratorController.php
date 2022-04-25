<?php

namespace App\Controller;

use App\Entity\LobbyWaitungUser;
use App\Entity\Rooms;
use App\Entity\User;
use App\Helper\JitsiAdminController;
use App\Service\caller\CallerSessionService;
use App\Service\Lobby\DirectSendService;
use App\Service\Lobby\LobbyUtils;
use App\Service\RoomService;
use App\Service\Lobby\ToModeratorWebsocketService;
use App\Service\Lobby\ToParticipantWebsocketService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class LobbyModeratorController extends JitsiAdminController
{

    private $toModerator;
    private $toParticipant;
    private $directSend;

    public function __construct(ManagerRegistry               $managerRegistry,
                                TranslatorInterface           $translator,
                                LoggerInterface               $logger,
                                ParameterBagInterface         $parameterBag,
                                DirectSendService             $directSendService,
                                ToParticipantWebsocketService $toParticipantWebsocketService,
                                ToModeratorWebsocketService   $toModeratorWebsocketService
    )
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
        $this->toModerator = $toModeratorWebsocketService;
        $this->toParticipant = $toParticipantWebsocketService;
        $this->directSend = $directSendService;
    }


    /**
     * @Route("/room/lobby/moderator/{type}/{uid}", name="lobby_moderator", defaults={"type" = "a"})
     */
    public function index(Request $request, $uid, $type): Response
    {
        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(array('uidReal' => $uid));
        if ($this->checkPermissions($room, $this->getUser())) {
            return $this->render('lobby/index.html.twig', [
                'room' => $room,
                'server' => $room->getServer(),
                'type' => $type,
                'user' => $this->getUser()
            ]);
        }

        $this->logger->log('error', 'User trys to enter Lobby which he is no moderator of', array('room' => $room->getId(), 'user' => $this->getUser()->getUserIdentifier()));
        return $this->redirectToRoute('dashboard', array('snack' => $this->translator->trans('error.noPermission'), 'color' => 'danger'));

    }

    /**
     * @Route("/room/lobby/start/moderator/{t}/{room}", name="lobby_moderator_start")
     */
    public function startMeeting($room, $t, RoomService $roomService): Response
    {
        $roomL = $this->doctrine->getRepository(Rooms::class)->findOneBy(array('uidReal' => $room));
        if (!$this->checkPermissions($roomL, $this->getUser())) {
            $this->logger->log('error', 'User trys to enter room which he is no moderator of', array('room' => $roomL->getId(), 'user' => $this->getUser()->getUserIdentifier()));
            return $this->redirectToRoute('dashboard', array('snack' => $this->translator->trans('Fehler'), 'color' => 'danger'));
        }
        $url = $roomService->join($roomL, $this->getUser(), $t, $this->getUser()->getFormatedName($this->parameterBag->get('laf_showNameInConference')));
        return $this->redirect($url);
    }

    /**
     * @Route("/room/lobby/accept/{wUid}", name="lobby_moderator_accept")
     */
    public function accept(Request $request, $wUid, CallerSessionService $callerSessionService): Response
    {
        $lobbyUser = $this->doctrine->getRepository(LobbyWaitungUser::class)->findOneBy(array('uid' => $wUid));
        if (!$lobbyUser) {
            return new JsonResponse(array('error' => false, 'message' => $this->translator->trans('lobby.moderator.accept.error'), 'color' => 'danger'));
        }
        $room = $lobbyUser->getRoom();
        if (!$this->checkPermissions($room, $this->getUser())) {
            $this->logger->log('error', 'User trys to enter room which he is no moderator of', array('room' => $room->getId(), 'user' => $this->getUser()->getUserIdentifier()));
            return new JsonResponse(array('error' => false, 'message' => $this->translator->trans('lobby.moderator.accept.error'), 'color' => 'danger'));
        }
        $em = $this->doctrine->getManager();
        $callerSessionService->acceptCallerUser($lobbyUser);

        $em->remove($lobbyUser);
        $em->flush();

        $this->toParticipant->acceptLobbyUser($lobbyUser);
        $this->toModerator->refreshLobby($lobbyUser);
        $this->toModerator->participantLeftLobby($lobbyUser);
        return new JsonResponse(array('error' => false, 'message' => $this->translator->trans('lobby.moderator.accept.success'), 'color' => 'success'));
    }

    /**
     * @Route("/room/lobby/acceptAll/{roomId}", name="lobby_moderator_accept_all")
     */
    public function acceptAll(Request $request, $roomId, CallerSessionService $callerSessionService): Response
    {
        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(array('uidReal' => $roomId));
        if (!$this->checkPermissions($room, $this->getUser())) {
            $this->logger->log('error', 'User trys to enter room which he is no moderator of', array('room' => $room->getId(), 'user' => $this->getUser()->getUserIdentifier()));
            return new JsonResponse(array('error' => false, 'message' => $this->translator->trans('lobby.moderator.accept.error'), 'color' => 'danger'));
        }
        $lobbyUser = $room->getLobbyWaitungUsers();
        $em = $this->doctrine->getManager();
        foreach ($lobbyUser as $data) {
            $callerSessionService->acceptCallerUser($data);
            $em->remove($data);
            $em->flush();
            $this->toParticipant->acceptLobbyUser($data);
            $this->toModerator->participantLeftLobby($lobbyUser);
        }
        $this->toModerator->refreshLobby($data);
        return new JsonResponse(array('error' => false, 'message' => $this->translator->trans('lobby.moderator.accept.all.success'), 'color' => 'success'));
    }

    /**
     * @Route("/room/lobby/decline/{wUid}", name="lobby_moderator_decline")
     */
    public function decline($wUid): Response
    {
        $lobbyUser = $this->doctrine->getRepository(LobbyWaitungUser::class)->findOneBy(array('uid' => $wUid));
        if (!$lobbyUser) {
            return new JsonResponse(array('error' => false, 'message' => $this->translator->trans('lobby.moderator.accept.error'), 'color' => 'danger'));
        }
        $room = $lobbyUser->getRoom();
        if (!$this->checkPermissions($room, $this->getUser())) {
            $this->logger->log('error', 'User trys to enter room which he is no moderator of', array('room' => $room->getId(), 'user' => $this->getUser()->getUserIdentifier()));
            return new JsonResponse(array('error' => false, 'message' => $this->translator->trans('lobby.moderator.accept.error'), 'color' => 'danger'));
        }
        $em = $this->doctrine->getManager();
        try {
            if ($lobbyUser->getCallerSession()) {
                $session = $lobbyUser->getCallerSession();
                $session->setLobbyWaitingUser(null);
                $em->persist($session);
                $em->flush();
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        $em->remove($lobbyUser);
        $em->flush();
        $this->toParticipant->sendDecline($lobbyUser);
        $this->toModerator->refreshLobby($lobbyUser);
        $this->toModerator->participantLeftLobby($lobbyUser);
        return new JsonResponse(array('error' => false, 'message' => $this->translator->trans('lobby.moderator.decline.success'), 'color' => 'success'));
    }

    /**
     * @Route("/lobby/moderator/endMeeting/{roomUid}", name="lobby_Moderator_endMeeting")
     */
    public function broadcastWebsocketEndMeeting($roomUid, LobbyUtils $lobbyUtils): Response
    {
        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(array('uidReal' => $roomUid));
        if ($room) {
            if ($this->checkPermissions($room, $this->getUser())) {
                $lobbyUtils->cleanLobby($room);
                $this->directSend->sendModal(
                    'lobby_broadcast_websocket/' . $room->getUidReal(),
                    $this->renderView('lobby_participants/endMeeting.html.twig', array('url' => $this->generateUrl('index')))
                );

                $this->directSend->sendEndMeeting(
                    'lobby_broadcast_websocket/' . $room->getUidReal(),
                    $this->generateUrl('index'),
                    $this->parameterBag->get('laf_lobby_popUpDuration')
                );
                return new JsonResponse(array('error' => false));
            }
        }
        return new JsonResponse(array('error' => true));
    }


    private function checkPermissions(Rooms $room, ?User $user)
    {
        if ($room->getModerator() === $user) {
            return true;
        }
        if ($user->getPermissionForRoom($room)->getLobbyModerator() === true) {
            return true;
        }
        return false;
    }


}