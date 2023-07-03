<?php

namespace App\Service\caller;

use App\Entity\CallerId;
use App\Entity\CallerRoom;
use App\Entity\Rooms;
use Doctrine\ORM\EntityManagerInterface;
use Prophecy\Call\Call;

class CallerPrepareService
{
    private $em;
    private $callerService;
    private $callerPinService;
    private $callerSessionService;
    private $callerLeftService;

    public function __construct(CallerLeftService $callerLeftService, CallerSessionService $callerSessionService, CallerPinService $callerPinService, EntityManagerInterface $entityManager, CallerFindRoomService $callerService)
    {
        $this->em = $entityManager;
        $this->callerService = $callerService;
        $this->callerPinService = $callerPinService;
        $this->callerSessionService = $callerSessionService;
        $this->callerLeftService = $callerLeftService;
    }

    /**
     * This Function creates Caller Ids for all Rooms and all USers which are participants in the rooms
     * @return void
     */
    public function prepareCallerId()
    {
        $this->addNewId();;
        $this->deleteOldId();
    }

    /**
     * @return float|int|mixed|string
     * This FUnction delete Caller Ids from Rooms which are in the past and the call id is not need anymore
     */
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

    /**
     * This Function adds new Ids to all Rooms in the future and persistant rooms.
     * @return float|int|mixed|string
     */
    public function addNewId()
    {
        $now = (new \DateTime())->getTimestamp();
        $futureRooms = $this->em->getRepository(Rooms::class)->findFutureRoomsWithNoCallerId($now);
        foreach ($futureRooms as $data) {
            $this->addCallerIdToRoom($data);
        }
        return $futureRooms;
    }

    /**
     * Adds a caller Room Id to the given Room
     * @param Rooms $rooms Room to check if the room has a caller Id and if not then add a caller Id
     * @return CallerRoom|null
     */
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
            $rooms->setCallerRoom($callerId);
        }
        return $callerId;
    }

    /**
     * generates the random Caller ID. The Function checks if the caller Id is already used
     * @param $max
     * @return string
     */
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

    /**
     * @param $random
     * @return bool
     * Checks if the random Id is already used
     */
    public function checkRandomId($random): bool
    {
        $finding = $this->em->getRepository(CallerRoom::class)->findOneBy(['callerId' => $random]);
        return $finding ? true : false;
    }

    /**
     * Then it adds a PIN for every Participant
     * @return float|int|mixed|string
     * This Function serches for all Rooms which are in the fuuture or persistant rooms
     */
    public function createUserCallerId()
    {
        $rooms = $this->em->getRepository(Rooms::class)->findRoomsnotInPast();
        foreach ($rooms as $data) {
            $this->createUserCallerIDforRoom($data);
        }
        return $rooms;
    }


    /**
     * Generates callerId for a given Room
     * @param Rooms $rooms
     * @return CallerId[]|\Doctrine\Common\Collections\Collection
     */
    public function createUserCallerIDforRoom(Rooms $rooms)
    {

        foreach ($rooms->getUser() as $data) {
            $callerID = $this->em->getRepository(CallerId::class)->findOneBy(['room' => $rooms, 'user' => $data]);
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

    /**
     * Creates the unique Caller PIN for this it needs the room to search if no other user has the same caller Id
     * @param Rooms $rooms
     * @param $max
     * @return string
     */
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

    /**
     * CHecks if the id is already added to the room
     * @param $random
     * @param Rooms $rooms
     * @return bool
     */
    public function checkRandomCallerUserId($random, Rooms $rooms): bool
    {
        $finding = $this->em->getRepository(CallerId::class)->findOneBy(['callerId' => $random, 'room' => $rooms]);
        return $finding ? true : false;
    }
}
