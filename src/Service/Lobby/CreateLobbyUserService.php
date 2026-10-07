<?php

namespace App\Service\Lobby;

use App\Entity\LobbyWaitungUser;
use App\Entity\Rooms;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CreateLobbyUserService
{
    public function __construct(
        private readonly EntityManagerInterface      $em,
        private readonly ToModeratorWebsocketService $toModerator,
        private readonly ParameterBagInterface       $parameterBag
    ) {
    }

    public function createNewLobbyUser(User $user, Rooms $room, string $type, bool $websocketReady = false): LobbyWaitungUser
    {
        $lobbyUser = $this->em->getRepository(LobbyWaitungUser::class)->findOneBy(['user' => $user, 'room' => $room]);
        if (!$lobbyUser) {
            $lobbyUser = new LobbyWaitungUser();
            $lobbyUser->setWebsocketReady(websocketReady: $websocketReady);
            $lobbyUser->setType($type);
            $lobbyUser->setUser($user);
            $lobbyUser->setRoom($room);
            $lobbyUser->setCreatedAt(new \DateTimeImmutable());
            $lobbyUser->setUid(md5(uniqid()));
            /** @var string $showNameInConference */
            $showNameInConference = $this->parameterBag->get('laf_showNameInConference');
            $lobbyUser->setShowName($user->getFormatedName($showNameInConference));

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
