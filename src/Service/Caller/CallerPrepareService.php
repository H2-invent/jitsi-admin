<?php

namespace App\Service\Caller;

use App\Entity\CallerId;
use App\Entity\CallerRoom;
use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Repository\CallerRoomRepository;
use App\Repository\RoomsRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Prophecy\Call\Call;

class CallerPrepareService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * This Function creates Caller Ids for all Rooms and all USers which are participants in the rooms
     */
    public function prepareCallerId(): void
    {
        $this->addNewId();;
        $this->deleteOldId();
    }

    /**
     * @return CallerRoom[]
     * This FUnction delete Caller Ids from Rooms which are in the past and the call id is not need anymore
     */
    public function deleteOldId(): array
    {
        /** @var CallerRoomRepository $callerRoomRepository */
        $callerRoomRepository = $this->em->getRepository(CallerRoom::class);
        $now                  = new \DateTimeImmutable()->getTimestamp();
        $oldCallerId          = $callerRoomRepository->findPastRoomsWithCallerId($now);
        foreach ($oldCallerId as $data) {
            $this->em->remove($data);
            $this->em->flush();
        }

        return $oldCallerId;
    }

    /**
     * This Function adds new Ids to all Rooms in the future and persistant rooms.
     * @return Rooms[]
     */
    public function addNewId(): array
    {
        /** @var RoomsRepository $roomsRepository */
        $roomsRepository = $this->em->getRepository(Rooms::class);
        $now             = new \DateTimeImmutable()->getTimestamp();
        $futureRooms     = $roomsRepository->findFutureRoomsWithNoCallerId($now);
        foreach ($futureRooms as $data) {
            $this->addCallerIdToRoom($data);
        }

        return $futureRooms;
    }

    /**
     * Adds a caller Room Id to the given Room
     * @param Rooms $rooms Room to check if the room has a caller Id and if not then add a caller Id
     */
    public function addCallerIdToRoom(Rooms $rooms): ?CallerRoom
    {
        $callerId = $rooms->getCallerRoom();

        if (!$callerId) {
            $callerId = new CallerRoom();
            $callerId->setRoom($rooms);
            $callerId->setCreatedAt(new \DateTimeImmutable());
            $callerId->setCallerId($this->generateRoomId(999999));
            $this->em->persist($callerId);
            $this->em->flush();
            $rooms->setCallerRoom($callerId);
        }

        return $callerId;
    }

    /**
     * generates the random Caller ID. The Function checks if the caller Id is already used
     */
    public function generateRoomId(int $max): string
    {
        $finding = false;
        do {
            $rand    = strval(random_int(0, $max));
            $length  = strlen(strval($max));
            $rand    = str_pad($rand, $length, '0');
            $finding = $this->checkRandomId($rand);
        } while ($finding == true);

        return $rand;
    }

    /**
     * @return bool
     * Checks if the random Id is already used
     */
    public function checkRandomId(string $random): bool
    {
        $finding = $this->em->getRepository(CallerRoom::class)->findOneBy(['callerId' => $random]);

        return $finding ? true : false;
    }

    /**
     * Then it adds a PIN for every Participant
     * @return Rooms[]
     * This Function serches for all Rooms which are in the fuuture or persistant rooms
     */
    public function createUserCallerId(): array
    {
        /** @var RoomsRepository $roomsRepository */
        $roomsRepository = $this->em->getRepository(Rooms::class);
        $rooms           = $roomsRepository->findRoomsnotInPast();
        foreach ($rooms as $data) {
            $this->createUserCallerIDforRoom($data);
        }

        return $rooms;
    }


    /**
     * Generates callerId for a given Room
     * @return Collection<int, CallerId>
     */
    public function createUserCallerIDforRoom(Rooms $rooms): Collection
    {
        foreach ($rooms->getUser() as $data) {
            $callerID = $this->em->getRepository(CallerId::class)->findOneBy(['room' => $rooms, 'user' => $data]);
            if (!$callerID) {
                $callerID = new CallerId();
                $callerID
                    ->setRoom($rooms)
                    ->setUser($data)
                    ->setCreatedAt(new \DateTimeImmutable())
                    ->setCallerId($this->generateCallerUserId($rooms, 999999));
                $rooms->addCallerId($callerID);
            }
            $this->em->persist($callerID);
        }
        $this->em->flush();

        return $rooms->getCallerIds();
    }

    /**
     * Generates callerId for a given Room
     */
    public function createUserCallerIDforRepeater(Repeat $repeat): void
    {
        $prototype = $repeat->getPrototyp();
        $rooms = $repeat->getRooms()->toArray();
        if ($rooms === []) {
            return;
        }

        // Load the PINs of all appointments in one query instead of one lookup per appointment and user.
        $roomIds = array_map(static fn(Rooms $room) => $room->getId(), $rooms);
        $existingCallerIds = $this->em->getRepository(CallerId::class)->findByRooms($roomIds);

        $existingByRoomAndUser = [];
        foreach ($existingCallerIds as $callerId) {
            $existingByRoomAndUser[$callerId->getRoom()->getId() . ':' . $callerId->getUser()?->getId()] = true;
        }

        foreach ($prototype->getPrototypeUsers() as $pUser) {
            foreach ($rooms as $room) {
                if (isset($existingByRoomAndUser[$room->getId() . ':' . $pUser->getId()])) {
                    continue;
                }
                $callerID = new CallerId();
                $callerID
                    ->setRoom($room)
                    ->setUser($pUser)
                    ->setCreatedAt(new \DateTimeImmutable())
                    ->setCallerId($this->generateCallerUserId($room, 999999));
                $this->em->persist($callerID);
                $room->addCallerId($callerID);
            }
        }

        $this->em->flush();
    }


    /**
     * Creates the unique Caller PIN for this it needs the room to search if no other user has the same caller Id
     */
    public function generateCallerUserId(Rooms $rooms, int $max): string
    {
        do {
            $rand    = strval(random_int(0, $max));
            $length  = strlen(strval($max));
            $rand    = str_pad($rand, $length, '0');
            $finding = $this->checkRandomCallerUserId($rand, $rooms);
        } while ($finding == true);

        return $rand;
    }

    /**
     * CHecks if the id is already added to the room
     */
    public function checkRandomCallerUserId(string $random, Rooms $rooms): bool
    {
        foreach ($rooms->getCallerIds() as $data) {
            if ($random === $data->getCallerId()) {
                return true;
            }
        }

        return false;
    }
}
