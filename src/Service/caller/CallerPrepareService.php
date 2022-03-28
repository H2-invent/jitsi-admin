<?php

namespace App\Service\caller;

use App\Entity\CallerId;
use App\Entity\CallerRoom;
use App\Entity\Rooms;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Prophecy\Call\Call;

class CallerPrepareService
{
    private $em;
    private $callerService;
    private $callerPinService;

    public function __construct(CallerPinService $callerPinService, EntityManagerInterface $entityManager, CallerFindRoomService $callerService)
    {
        $this->em = $entityManager;
        $this->callerService = $callerService;
        $this->callerPinService = $callerPinService;
    }

    public function prepareCallerId()
    {
        $this->addNewId();;
        $this->deleteOldId();

    }

    public function deleteOldId()
    {
        $now = (new \DateTime())->getTimestamp();
        $oldCallerId = $this->em->getRepository(CallerRoom::class)->findPastRoomsWithCallerId($now);
        foreach ($oldCallerId as $data) {
                $this->em->remove($data);
            $this->em->flush();
        }
        return $oldCallerId;
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

    public function createUserCallerId(){
        $rooms = $this->em->getRepository(Rooms::class)->findRoomsnotInPast();
        foreach ($rooms as $data){
            $this->createUserCallerIDforRoom($data);
        }
        return $rooms;
    }


    public function createUserCallerIDforRoom(Rooms $rooms)
    {

        foreach ($rooms->getUser() as $data) {
            $callerID = $this->em->getRepository(CallerId::class)->findOneBy(array('room' => $rooms, 'user' => $data));
            if (!$callerID) {
                $callerID = new CallerId();
                $callerID
                    ->setRoom($rooms)
                    ->setUser($data)
                    ->setCreatedAt(new \DateTime())
                    ->setCallerId($this->generateCallerUserId($rooms, 999999));
                $rooms->addCallerId($callerID);
           }
            $this->em->persist($callerID);
        }
        $this->em->flush();
        return $rooms->getCallerIds();
    }

    public function generateCallerUserId(Rooms $rooms, $max): string
    {
        $finding = false;
        do {
            $rand = strval(rand(0, $max));
            $length = strlen(strval($max));
            $rand = str_pad($rand, $length, '0');
            $finding = $this->checkRandomCallerUserId($rand, $rooms);
        } while ($finding == true);
        return $rand;
    }

    public function checkRandomCallerUserId($random, Rooms $rooms): bool
    {
        $finding = $this->em->getRepository(CallerId::class)->findOneBy(array('callerId' => $random,'room'=>$rooms));
        return $finding ? true : false;
    }
}