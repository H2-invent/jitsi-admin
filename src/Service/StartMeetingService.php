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
    private $roomService;
    private $em;
    private $urlGen;
    private $parameterBag;
    private $translator;
    private $twig;
    private $url;
    private $room;
    private $user;
    private $type;
    private $name;
    private $toModerator;
    private $logger;
    public function __construct(LoggerInterface $logger,ToModeratorWebsocketService $toModeratorWebsocketService, Environment $environment, RoomService $roomService, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator, ParameterBagInterface $parameterBag, TranslatorInterface $translator)
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

    public function startMeeting(?Rooms $room, User $user, $t,$name)
    {
        $this->room = $room;
        $this->user = $user;
        $this->type = $t;
        $this->name = $name;
        if ($room && in_array($user, $room->getUser()->toarray())) {
            $this->url = $this->roomService->join($room, $user, $t, $name);
            if ($user === $room->getModerator() && $room->getTotalOpenRooms() && $room->getPersistantRoom()) {
                $room->setStart(new \DateTime());
                if ($room->getTotalOpenRoomsOpenTime()) {
                    $room->setEnddate((new \DateTime())->modify('+ ' . $room->getTotalOpenRoomsOpenTime() . ' min'));
                }
                $this->em->persist($room);
                $this->em->flush();
            }
            $now = new \DateTime();
            if ($room->getTimeZone()) {
                $now = new \DateTime('now', TimeZoneService::getTimeZone($user));
            }
            $now =  new \DateTime('now', new \DateTimeZone('utc'));
            $start = null;
            $endDate = null;
            if(!$room->getPersistantRoom()){

                $start = (clone $room->getStartUtc())->modify('-30min');
                $endDate = clone $room->getEndDateUtc();
            }


            if (($room->getPersistantRoom() || $start < $now && $endDate > $now) || $user === $room->getModerator()) {
                if ($room->getLobby()) {
                    return $this->generateLobby();
                }
                return $this->roomValid();
            }
            return $this->createRoomClosed();
        }
        return $this->roomNotFound();
    }

    private function generateLobby()
    {
        if ($this->user === $this->room->getModerator() || $this->user->getPermissionForRoom($this->room)->getLobbyModerator()) {

            if ($this->room->getModerator() === $this->user || $this->user->getPermissionForRoom($this->room)->getLobbyModerator() === true) {
                return new Response($this->twig->render('lobby/index.html.twig', [
                    'room' => $this->room,
                    'server' => $this->room->getServer(),
                    'type' => $this->type,
                    'name'=>$this->name,
                    'user'=>$this->user
                ])
                );
            }

            $this->logger->log('error', 'User trys to enter Lobby which he is no moderator of', array('room' => $this->room->getId(), 'user' => $this->user->getUserIdentifier()));
            $res = $this->urlGen->generate('dashboard', array('snack' => $this->translator->trans('error.noPermission'), 'color' => 'danger'));
        } else {

            $lobbyUser = $this->em->getRepository(LobbyWaitungUser::class)->findOneBy(array('user'=>$this->user,'room'=>$this->room));
            if(!$lobbyUser){
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

            return new Response($this->twig->render('lobby_participants/index.html.twig',array('type'=>$lobbyUser->getType(),'room'=>$lobbyUser->getRoom(), 'server'=>$lobbyUser->getRoom()->getServer(),'user'=>$lobbyUser->getUser())));
        }
        return new RedirectResponse($res);

    }

    private function createRoomClosed()
    {
        return new RedirectResponse($this->urlGen->generate('dashboard', ['color' => 'danger', 'snack' => $this->translator->trans('Der Beitritt ist nur von {from} bis {to} möglich',
                array(
                    '{from}' => $this->room->getStartwithTimeZone($this->user)->modify('-30min')->format('d.m.Y H:i'),
                    '{to}' => $this->room->getEndwithTimeZone($this->user)->format('d.m.Y H:i')
                ))
            ]
        ));
    }

    private function roomNotFound()
    {
        return new RedirectResponse($this->urlGen->generate('dashboard', [
                'color' => 'danger',
                'snack' => $this->translator->trans('Konferenz nicht gefunden. Zugangsdaten erneut eingeben')
            ]
        ));
    }

    private function roomValid()
    {
        if ($this->type === 'a') {
            return new RedirectResponse($this->url);
        } elseif ($this->type === 'b') {
            return new Response($this->twig->render('start/index.html.twig', array('room' => $this->room, 'user' => $this->user,'name'=>$this->name)));
        }
        return new NotFoundHttpException('Room not found');
    }
}
