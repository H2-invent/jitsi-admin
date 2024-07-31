<?php

namespace App\Controller;

use App\Entity\LobbyWaitungUser;
use App\Entity\Rooms;
use App\Helper\JitsiAdminController;
use App\Service\caller\CallerSessionService;
use App\Service\CheckLobbyPermissionService;
use App\Service\Lobby\DirectSendService;
use App\Service\Lobby\LobbyUtils;
use App\Service\Lobby\ToModeratorWebsocketService;
use App\Service\Lobby\ToParticipantWebsocketService;
use App\Service\RoomService;
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
    private CheckLobbyPermissionService $checkLobbyPermissionService;

    public function __construct(
        ManagerRegistry               $managerRegistry,
        TranslatorInterface           $translator,
        LoggerInterface               $logger,
        ParameterBagInterface         $parameterBag,
        DirectSendService             $directSendService,
        ToParticipantWebsocketService $toParticipantWebsocketService,
        ToModeratorWebsocketService   $toModeratorWebsocketService,
        CheckLobbyPermissionService   $checkLobbyPermissionService
    )
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
        $this->toModerator = $toModeratorWebsocketService;
        $this->toParticipant = $toParticipantWebsocketService;
        $this->directSend = $directSendService;
        $this->checkLobbyPermissionService = $checkLobbyPermissionService;
    }


    #[Route(path: '/room/lobby/moderator/{type}/{uid}', name: 'lobby_moderator', defaults: ['type' => 'a'])]
    public function index(Request $request, $uid, $type): Response
    {
        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(['uidReal' => $uid]);

        if ($this->checkLobbyPermissionService->checkPermissions($room, $this->getSessionUser($request->getSession()))) {

            return $this->render(
                'lobby/index.html.twig',
                [
                    'room' => $room,
                    'server' => $room->getServer(),
                    'type' => $type,
                    'user' => $this->getSessionUser($request->getSession())
                ]
            );
        }

        $this->logger->log('error', 'User trys to enter Lobby which he is no moderator of', ['room' => $room->getId(), 'user' => $this->getSessionUser($request->getSession())]);
//        $this->addFlash('danger', $this->translator->trans('error.noPermission'));
        return $this->redirectToRoute('dashboard');
    }

    #[Route(path: '/room/lobby/start/moderator/{t}/{room}', name: 'lobby_moderator_start')]
    public function startMeeting($room, $t, RoomService $roomService, Request $request): Response
    {
        $roomL = $this->doctrine->getRepository(Rooms::class)->findOneBy(['uidReal' => $room]);
        if (!$this->checkLobbyPermissionService->checkPermissions($roomL, $this->getSessionUser($request->getSession()))) {
            $this->logger->log('error', 'User trys to enter room which he is no moderator of', ['room' => $roomL->getId(), 'user' => $this->getUser()->getUserIdentifier()]);
            $this->addFlash('danger', $this->translator->trans('Fehler'));
            return $this->redirectToRoute('dashboard');
        }
        $url = $roomService->join($roomL, $this->getUser(), $t, $this->getSessionUser($request->getSession())->getFormatedName($this->parameterBag->get('laf_showNameInConference')));
        return $this->redirect($url);
    }

    #[Route(path: '/room/lobby/accept/{wUid}', name: 'lobby_moderator_accept')]
    public function accept(Request $request, $wUid, CallerSessionService $callerSessionService): Response
    {
        $lobbyUser = $this->doctrine->getRepository(LobbyWaitungUser::class)->findOneBy(['uid' => $wUid]);
        if (!$lobbyUser) {
            return new JsonResponse(['error' => false, 'message' => $this->translator->trans('lobby.participant.notInLobby'), 'color' => 'warning']);
        }
        $room = $lobbyUser->getRoom();
        if (!$this->checkLobbyPermissionService->checkPermissions($room, $this->getSessionUser($request->getSession()))) {
            $this->logger->log('error', 'User trys to enter room which he is no moderator of', ['room' => $room->getId(), 'user' => $this->getUser()->getUserIdentifier()]);
            return new JsonResponse(['error' => false, 'message' => $this->translator->trans('lobby.moderator.accept.error'), 'color' => 'danger']);
        }
        $em = $this->doctrine->getManager();
        $callerSessionService->acceptCallerUser($lobbyUser);

        $em->remove($lobbyUser);
        $em->flush();

        $this->toParticipant->acceptLobbyUser($lobbyUser);
        $this->toModerator->refreshLobby($lobbyUser);
        $this->toModerator->participantLeftLobby($lobbyUser);
        return new JsonResponse(['error' => false, 'message' => $this->translator->trans('lobby.moderator.accept.success'), 'color' => 'success']);
    }

    #[Route(path: '/room/lobby/acceptAll/{roomId}', name: 'lobby_moderator_accept_all')]
    public function acceptAll(Request $request, $roomId, CallerSessionService $callerSessionService): Response
    {
        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(['uidReal' => $roomId]);
        if (!$this->checkLobbyPermissionService->checkPermissions($room, $this->getSessionUser($request->getSession()))) {
            $this->logger->log('error', 'User trys to enter room which he is no moderator of', ['room' => $room->getId(), 'user' => $this->getUser()->getUserIdentifier()]);
            return new JsonResponse(['error' => false, 'message' => $this->translator->trans('lobby.moderator.accept.error'), 'color' => 'danger']);
        }
        $lobbyUser = $room->getLobbyWaitungUsers();
        $em = $this->doctrine->getManager();
        $lastUser = null;
        foreach ($lobbyUser as $data) {
            $callerSessionService->acceptCallerUser($data);
            $em->remove($data);
            $em->flush();
            $this->toParticipant->acceptLobbyUser($data);
            $this->toModerator->participantLeftLobby($data);
            $lastUser = $data;
        }
        if ($lastUser) {
            $this->toModerator->refreshLobby($lastUser);
        }
        return new JsonResponse(['error' => false, 'message' => $this->translator->trans('lobby.moderator.accept.all.success'), 'color' => 'success']);
    }

    #[Route(path: '/room/lobby/decline/{wUid}', name: 'lobby_moderator_decline')]
    public function decline($wUid, Request $request): Response
    {
        $lobbyUser = $this->doctrine->getRepository(LobbyWaitungUser::class)->findOneBy(['uid' => $wUid]);
        if (!$lobbyUser) {
            return new JsonResponse(['error' => false, 'message' => $this->translator->trans('lobby.participant.notInLobby'), 'color' => 'danger']);
        }
        $room = $lobbyUser->getRoom();
        if (!$this->checkLobbyPermissionService->checkPermissions($room, $this->getSessionUser($request->getSession()))) {
            $this->logger->log('error', 'User trys to enter room which he is no moderator of', ['room' => $room->getId(), 'user' => $this->getUser()->getUserIdentifier()]);
            return new JsonResponse(['error' => false, 'message' => $this->translator->trans('lobby.moderator.accept.error'), 'color' => 'danger']);
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
        return new JsonResponse(['error' => false, 'message' => $this->translator->trans('lobby.moderator.decline.success'), 'color' => 'success']);
    }

    #[Route(path: '/lobby/moderator/endMeeting/{roomUid}', name: 'lobby_Moderator_endMeeting')]
    public function broadcastWebsocketEndMeeting($roomUid, LobbyUtils $lobbyUtils, Request $request): Response
    {
        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(['uidReal' => $roomUid]);
        if ($room) {
            if ($this->checkLobbyPermissionService->checkPermissions($room, $this->getSessionUser($request->getSession()))) {
                $lobbyUtils->cleanLobby($room);
                $this->directSend->sendEndMeeting(
                    'lobby_broadcast_websocket/' . $room->getUidReal(),
                    $this->generateUrl('index'),
                    $this->parameterBag->get('laf_lobby_popUpDuration')
                );
                return new JsonResponse(['error' => false]);
            }
        }
        return new JsonResponse(['error' => true]);
    }
}
