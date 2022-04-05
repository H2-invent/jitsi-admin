<?php

namespace App\Service\Lobby;

use App\Entity\LobbyWaitungUser;
use App\Entity\Rooms;
use Doctrine\ORM\EntityManagerInterface;

class LobbyUtils
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function cleanLobby(Rooms $rooms)
    {
        $lobbyUser = $this->em->getRepository(LobbyWaitungUser::class)->findBy(array('room' => $rooms));
        foreach ($lobbyUser as $data) {
            $this->em->remove($data);
        }
        $this->em->flush();
        return true;
    }
}