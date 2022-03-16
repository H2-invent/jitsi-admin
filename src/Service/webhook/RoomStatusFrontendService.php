<?php

namespace App\Service\webhook;

use App\Entity\Rooms;
use App\Entity\RoomStatus;
use App\Entity\RoomStatusParticipant;
use Doctrine\ORM\EntityManagerInterface;

class RoomStatusFrontendService
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function isRoomCreated(Rooms $rooms)
    {
        $roomStatus = $this->em->getRepository(RoomStatus::class)->findCreatedRooms($rooms);
        if ($roomStatus) {
            return true;
        }
        return false;
    }

    public function numberOfOccupants(Rooms $rooms)
    {
        $parts = $this->em->getRepository(RoomStatusParticipant::class)->findOccupantsOfRoom($rooms);
        return $parts;
    }
}