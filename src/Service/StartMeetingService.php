<?php

namespace App\Service;

use App\Entity\LobbyWaitungUser;
use App\Entity\Rooms;
use App\Entity\User;
use App\Service\Lobby\ToModeratorWebsocketService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class StartMeetingService
{
    /**
     * @var RoomService
     */
    private $roomService;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGen;
    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var Environment
     */
    private $twig;
    private $url;
    private $room;
    private $user;
    private $type;
    private $name;
    /**
     * @var ToModeratorWebsocketService
     */
    private $toModerator;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger, ToModeratorWebsocketService $toModeratorWebsocketService, Environment $environment, RoomService $roomService, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator, ParameterBagInterface $parameterBag, TranslatorInterface $translator)
    {
        $this->roomService = $roomService;
        $this->em = $entityManager;
        $this->urlGen = $urlGenerator;
        $this->parameterBag = $parameterBag;
        $this->translator = $translator;
        $this->twig = $environment;
        $this->toModerator = $toModeratorWebsocketService;
        $this->logger = $logger;
    }

    /**
     * @param Rooms|null $room
     * @param User $user
     * @param $t
     * @param $name
     * @return RedirectResponse|Response|NotFoundHttpException
     * @throws \Exception
     * This function check if the user is allowed to enter the meeting
     * this function checks if the meeting is already started or if it is too late or to early
     * This function checks if the room has the lobby function activated
     */
    public function startMeeting(?Rooms $room, User $user, $t, $name)
    {
        $this->room = $room;
        $this->user = $user;
        $this->type = $t;
        $this->name = $name;
        if ($room && in_array($user, $room->getUser()->toarray())) {
            $this->url = $this->roomService->join($room, $user, $t, $name);
            if (!self::checkTime($room, $user)) {
                return $this->RoomClosed();
            }

            if ($room->getLobby()) {
                return $this->generateLobby();
            }

            return $this->roomDefault();

        }
        return $this->roomNotFound();
    }

    public function setAttribute(Rooms $rooms, ?User $user, $t, $name)
    {
        $this->room = $rooms;
        $this->user = $user;
        $this->type = $t;
        $this->name = $name;
    }

    /**
     * @return RedirectResponse|Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * this function generates a page if the lobby is activated
     */
    private function generateLobby()
    {
        if ($this->user === $this->room->getModerator() || $this->user->getPermissionForRoom($this->room)->getLobbyModerator()) {
            return $this->lobbyModerator();
        } else {

            return $this->createLobbyParticipantResponse();
        }
    }

    /**
     * @return string|Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     *  this function generates the page for the lobby moderator
     */
    public function lobbyModerator()
    {
        if ($this->room->getModerator() === $this->user || $this->user->getPermissionForRoom($this->room)->getLobbyModerator() === true) {
            return $this->createLobbyModeratorResponse();
        }

        $this->logger->log('error', 'User trys to enter Lobby which he is no moderator of', array('room' => $this->room->getId(), 'user' => $this->user->getUserIdentifier()));
        return $this->urlGen->generate('dashboard', array('snack' => $this->translator->trans('error.noPermission'), 'color' => 'danger'));
    }

    public function createLobbyModeratorResponse()
    {
        return new Response($this->twig->render('lobby/index.html.twig', [
            'room' => $this->room,
            'server' => $this->room->getServer(),
            'type' => $this->type,
            'name' => $this->name,
            'user' => $this->user
        ])
        );
    }

    /**
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * this function generates the page for the participant
     */
    public function createLobbyParticipantResponse()
    {
        $lobbyUser = $this->em->getRepository(LobbyWaitungUser::class)->findOneBy(array('user' => $this->user, 'room' => $this->room));
        if (!$lobbyUser || $this->user === null) {
            $lobbyUser = new LobbyWaitungUser();
            $lobbyUser->setType($this->type);
            $lobbyUser->setUser($this->user);
            $lobbyUser->setRoom($this->room);
            $lobbyUser->setCreatedAt(new \DateTime());
            $lobbyUser->setUid(md5(uniqid()));
            $lobbyUser->setShowName($this->name);
            $this->em->persist($lobbyUser);
            $this->em->flush();
            $this->toModerator->newParticipantInLobby($lobbyUser);
            $this->toModerator->refreshLobby($lobbyUser);
        }
        $lobbyUser->setShowName($this->name);
        $lobbyUser->setType($this->type);
        $this->em->persist($lobbyUser);
        $this->em->flush();
        return new Response($this->twig->render('lobby_participants/index.html.twig', array('type' => $lobbyUser->getType(), 'room' => $lobbyUser->getRoom(), 'server' => $lobbyUser->getRoom()->getServer(), 'user' => $lobbyUser)));
    }

    /**
     * @return RedirectResponse
     * this function generates tthe redirect respnse when the room is closed.
     * So it is to early or to late to enter the room
     */
    private function RoomClosed()
    {
        return new RedirectResponse($this->urlGen->generate('dashboard', ['color' => 'danger', 'snack' => $this->translator->trans('Der Beitritt ist nur von {from} bis {to} möglich',
                array(
                    '{from}' => $this->room->getStartwithTimeZone($this->user)->modify('-30min')->format('d.m.Y H:i'),
                    '{to}' => $this->room->getEndwithTimeZone($this->user)->format('d.m.Y H:i')
                ))
            ]
        ));
    }

    /**
     * @return RedirectResponse
     * this function redirect to the dashboard when the room is not avalable. this can happens when the user is not a participent or the romm is not available
     */
    private function roomNotFound()
    {
        return new RedirectResponse($this->urlGen->generate('dashboard', [
                'color' => 'danger',
                'snack' => $this->translator->trans('Konferenz nicht gefunden. Zugangsdaten erneut eingeben')
            ]
        ));
    }

    /**
     * @return RedirectResponse|Response|NotFoundHttpException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * this function genereats a redirect to the meeting app or generate an iframe to load the jitsi window
     */
    public function roomDefault()
    {
        if ($this->type === 'a') {
            return new RedirectResponse($this->url);
        } elseif ($this->type === 'b') {
            return new Response($this->twig->render('start/index.html.twig', array('room' => $this->room, 'user' => $this->user, 'name' => $this->name)));
        }
        return new NotFoundHttpException('Room not found');
    }

    static public function checkTime(Rooms $room, User $user = null)
    {

        $now = new \DateTime('now', new \DateTimeZone('utc'));
        $start = null;
        $endDate = null;
        if (!$room->getPersistantRoom()) {
            $start = (clone $room->getStartUtc())->modify('-30min');
            $endDate = clone $room->getEndDateUtc();
        }


        if (($room->getPersistantRoom() || $start < $now && $endDate > $now) || $user === $room->getModerator()) {
            return true;
        }
        return false;
    }
}
