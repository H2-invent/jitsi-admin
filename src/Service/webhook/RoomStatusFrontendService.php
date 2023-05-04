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

    public function isRoomClosed(Rooms $rooms): bool
    {
        $status = $this->em->getRepository(RoomStatus::class)->findBy(['room' => $rooms]);

        if (sizeof($status) === 0) {
            return false;
        }
        if (!$rooms->getStart()) {
            return false;
        }
        foreach ($status as $data) {
            if ($data->getDestroyed() !== true) {
                return false;
            }
        }
        foreach ($status as $data) {
            if ($data->getDestroyedUtc() > $rooms->getStartUtc()) {
                return true;
            }
        }

        return false;
    }
}
