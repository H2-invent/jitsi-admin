<?php

namespace App\Controller;

use App\Entity\LobbyWaitungUser;
use App\Entity\Rooms;
use App\Entity\User;
use App\Helper\JitsiAdminController;
use App\Message\LobbyLeaverMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use App\Service\Lobby\CreateLobbyUserService;
use App\Service\Lobby\DirectSendService;
use App\Service\Lobby\ToModeratorWebsocketService;
use App\Service\Lobby\ToParticipantWebsocketService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;


class LobbyParticipantsController extends JitsiAdminController
{
    private $toModerator;
    private $toParticipant;
    private $createLobbyUserService;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(ManagerRegistry               $managerRegistry,
                                TranslatorInterface           $translator,
                                LoggerInterface               $logger,
                                ParameterBagInterface         $parameterBag,
                                CreateLobbyUserService        $createLobbyUserService,
                                ToParticipantWebsocketService $toParticipantWebsocketService,
                                ToModeratorWebsocketService   $toModeratorWebsocketService,
                                DirectSendService             $lobbyUpdateService,
                                EventDispatcherInterface      $eventDispatcher
    )
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
        $this->lobbyUpdateService = $lobbyUpdateService;
        $this->toModerator = $toModeratorWebsocketService;
        $this->toParticipant = $toParticipantWebsocketService;
        $this->eventDispatcher = $eventDispatcher;
        $this->createLobbyUserService = $createLobbyUserService;
    }

    /**
     * @Route("/lobby/participants/{type}/{roomUid}/{userUid}", name="lobby_participants_wait", defaults={"type" = "a"})
     */
    public function index($roomUid, $userUid, $type): Response
    {

        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(array('uidReal' => $roomUid));
        $user = $this->doctrine->getRepository(User::class)->findOneBy(array('uid' => $userUid));
        $lobbyUser = $this->createLobbyUserService->createNewLobbyUser($user, $room, $type);

        return $this->render('lobby_participants/index.html.twig', array('type' => $type, 'room' => $room, 'server' => $room->getServer(), 'user' => $lobbyUser));
    }

    /**
     * @Route("/lobby/healthcheck/participants/{userUid}", name="lobby_participants_healthCheck")
     */
    public function healthcheck($userUid): Response
    {
        $lobbyUser = $this->doctrine->getRepository(LobbyWaitungUser::class)->findOneBy(array('uid' => $userUid));
        if ($lobbyUser) {
            return new JsonResponse(array('error' => false));
        }

        return new JsonResponse(array('error' => true));

    }

    /**
     * @Route("/lobby/renew/participants/{userUid}", name="lobby_participants_renew")
     */
    public function renew($userUid): Response
    {
        $lobbyUser = $this->doctrine->getRepository(LobbyWaitungUser::class)->findOneBy(array('uid' => $userUid));
        if ($lobbyUser) {
            $this->toModerator->newParticipantInLobby($lobbyUser);
            $this->toModerator->refreshLobby($lobbyUser);
            return new JsonResponse(array('error' => false, 'message' => $this->translator->trans('lobby.participant.ask.sucess'), 'color' => 'success'));
        }
        return new JsonResponse(array('error' => true, 'message' => $this->translator->trans('Fehler')));
    }

    /**
     * @Route("/lobby/leave/participants/{userUid}", name="lobby_participants_leave")
     */
    public function remove($userUid, MessageBusInterface $bus): Response
    {
        $lobbyUser = $this->doctrine->getRepository(LobbyWaitungUser::class)->findOneBy(array('uid' => $userUid));
        if ($lobbyUser) {
            $em = $this->doctrine->getManager();
            $em->remove($lobbyUser);
            $em->flush();
            $this->toModerator->refreshLobby($lobbyUser);
            $this->toModerator->participantLeftLobby($lobbyUser);
            return new JsonResponse(array('error' => false));
        };
        return new JsonResponse(array('error' => true));
    }

    /**
     * @Route("/lobby/browser/leave/participants/{userUid}", name="lobby_participants_browser_leave")
     */
    public function browser($userUid, MessageBusInterface $bus): Response
    {

        $lobbyUser = $this->doctrine->getRepository(LobbyWaitungUser::class)->findOneBy(array('uid' => $userUid));
        if ($lobbyUser) {
            $em = $this->doctrine->getManager();
            $lobbyUser->setCloseBrowser(true);
            $em->persist($lobbyUser);
            $em->flush();
            $bus->dispatch(
                new Envelope(
                    new LobbyLeaverMessage($userUid), [
                        new DelayStamp(3000)
                    ]
                )
            );
            return new JsonResponse(array('error' => false));
        };
        return new JsonResponse(array('error' => true));
    }
}
