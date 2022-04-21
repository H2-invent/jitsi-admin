<?php

namespace App\Service\Lobby;

use App\Entity\LobbyWaitungUser;
use App\Entity\Rooms;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CreateLobbyUserService
{
    private $em;
    private $toModerator;
    private $parameterBag;

    public function __construct(EntityManagerInterface $entityManager,  ToModeratorWebsocketService $toModeratorWebsocketService, ParameterBagInterface $parameterBag)
    {
        $this->toModerator = $toModeratorWebsocketService;
        $this->parameterBag = $parameterBag;
        $this->em = $entityManager;
    }

    public function createNewLobbyUser(User $user, Rooms $room, $type):LobbyWaitungUser
    {
        $lobbyUser = $this->em->getRepository(LobbyWaitungUser::class)->findOneBy(array('user' => $user, 'room' => $room));
        if (!$lobbyUser) {
            $lobbyUser = new LobbyWaitungUser();
            $lobbyUser->setType($type);
            $lobbyUser->setUser($user);
            $lobbyUser->setRoom($room);
            $lobbyUser->setCreatedAt(new \DateTime());
            $lobbyUser->setUid(md5(uniqid()));
            $lobbyUser->setShowName($user->getFormatedName($this->parameterBag->get('laf_showNameInConference')));

            $this->em->persist($lobbyUser);
            $this->em->flush();

            $this->toModerator->newParticipantInLobby($lobbyUser);
        }
        $lobbyUser->setCloseBrowser(false);
        $lobbyUser->setType($type);
        $this->em->persist($lobbyUser);
        $this->em->flush();
        $this->toModerator->refreshLobby($lobbyUser);
        return $lobbyUser;
    }
}