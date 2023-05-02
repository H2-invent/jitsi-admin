<?php

namespace App\Service\Lobby;

use App\Entity\CallerSession;
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
        $callerSessions = $this->em->getRepository(CallerSession::class)->findCallerSessionsByRoom($rooms);
        foreach ($callerSessions as $data2) {
            $data2->setForceFinish(true);
            $data2->setLobbyWaitingUser(null);
            $this->em->persist($data2);
        }
        $this->em->flush();

        $lobbyUser = $this->em->getRepository(LobbyWaitungUser::class)->findBy(['room' => $rooms]);

        foreach ($lobbyUser as $data) {
            $this->em->remove($data);
        }
        $this->em->flush();

        return true;
    }
}
