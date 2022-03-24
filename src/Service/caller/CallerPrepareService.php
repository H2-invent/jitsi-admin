<?php

namespace App\Service\caller;

use App\Entity\CallerRoom;
use App\Entity\Rooms;
use Doctrine\ORM\EntityManagerInterface;

class CallerPrepareService
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function prepareCallerId()
    {
        $this->addNewId();;
        $this->deleteOldId();

    }

    public function deleteOldId()
    {
        $now = (new \DateTime())->getTimestamp();
        $oldCallerId = $this->em->getRepository(CallerRoom::class)->findAll();
        foreach ($oldCallerId as $data) {
            if ($data->getRoom()->getEndTimestamp() < $now) {
                $this->em->remove($data);
            }
            $this->em->flush();
        }
    }

    public function addNewId()
    {
        $now = (new \DateTime())->getTimestamp();
        $futureRooms = $this->em->getRepository(Rooms::class)->findFutureRoomsWithNoCallerId($now);
        foreach ($futureRooms as $data) {
            $this->addCallerIdToRoom($data);
        }
        return $futureRooms;
    }

    public function addCallerIdToRoom(Rooms $rooms)
    {
        $callerId = $rooms->getCallerRoom();
        if (!$callerId) {
            $callerId = new CallerRoom();
            $callerId->setRoom($rooms);
            $callerId->setCreatedAt(new \DateTime());
            $callerId->setCallerId($this->generateRoomId(999999));
            $this->em->persist($callerId);
            $this->em->flush();
        }
        return $callerId;
    }

    public function generateRoomId($max): string
    {
        $finding = false;
        do {
            $rand = strval(rand(0, $max));
            $length = strlen(strval($max));
            $rand = str_pad($rand, $length, '0');
            $finding = $this->checkRandomId($rand);
        } while ($finding == true);
        return $rand;
    }

    public function checkRandomId($random): bool
    {
        $finding = $this->em->getRepository(CallerRoom::class)->findOneBy(array('callerId' => $random));
        return $finding ? true : false;
    }
}