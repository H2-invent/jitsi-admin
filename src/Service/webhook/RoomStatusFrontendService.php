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

    public function getRoomCreatedStatusMap(array $roomIds): array
    {
        if (empty($roomIds)) {
            return [];
        }
        $qb = $this->em->getRepository(RoomStatus::class)->createQueryBuilder('rs');
        $statuses = $qb->select('IDENTITY(rs.room) as roomId')
            ->where($qb->expr()->in('rs.room', ':roomIds'))
            ->andWhere($qb->expr()->isNull('rs.destroyed'))
            ->setParameter('roomIds', $roomIds)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($statuses as $status) {
            $result[$status['roomId']] = true;
        }
        return $result;
    }

    public function getRoomOccupantsMap(array $roomIds): array
    {
        if (empty($roomIds)) {
            return [];
        }
        $qb = $this->em->getRepository(RoomStatusParticipant::class)->createQueryBuilder('rp');
        $occupants = $qb->select('IDENTITY(rs.room) as roomId', 'rp.participantName')
            ->innerJoin('rp.roomStatus', 'rs')
            ->where($qb->expr()->in('rs.room', ':roomIds'))
            ->andWhere('rp.inRoom = true')
            ->andWhere($qb->expr()->isNull('rs.destroyed'))
            ->setParameter('roomIds', $roomIds)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($occupants as $o) {
            $result[$o['roomId']][] = $o['participantName'];
        }
        return $result;
    }

    public function getRoomClosedStatusMap(array $roomIds): array
    {
        if (empty($roomIds)) {
            return [];
        }
        $qb = $this->em->getRepository(RoomStatus::class)->createQueryBuilder('rs');

        $active = $qb->select('DISTINCT IDENTITY(rs.room) as roomId')
            ->where($qb->expr()->in('rs.room', ':roomIds'))
            ->andWhere($qb->expr()->isNull('rs.destroyed'))
            ->setParameter('roomIds', $roomIds)
            ->getQuery()
            ->getResult();

        $roomsWithActiveStatus = [];
        foreach ($active as $row) {
            $roomsWithActiveStatus[$row['roomId']] = true;
        }

        $destroyed = $this->em->getRepository(RoomStatus::class)->createQueryBuilder('rs2')
            ->select(
                'IDENTITY(rs2.room) as roomId',
                'MAX(rs2.destroyedAt) as latestDestroyedAt'
            )
            ->innerJoin('rs2.room', 'room')
            ->where('rs2.room IN (:roomIds)')
            ->andWhere('rs2.destroyed = true')
            ->groupBy('rs2.room')
            ->setParameter('roomIds', $roomIds)
            ->getQuery()
            ->getResult();

        $destroyedRoomIds = [];
        $latestDestroyedMap = [];
        foreach ($destroyed as $row) {
            $destroyedRoomIds[] = $row['roomId'];
            $latestDestroyedMap[$row['roomId']] = $row['latestDestroyedAt'];
        }

        $roomStarts = [];
        if (!empty($destroyedRoomIds)) {
            $startResults = $this->em->getRepository(RoomStatus::class)->createQueryBuilder('rs3')
                ->select('room2.id as roomId', 'room2.startUtc')
                ->innerJoin('rs3.room', 'room2')
                ->where('rs3.room IN (:roomIds)')
                ->setParameter('roomIds', $destroyedRoomIds)
                ->setMaxResults(count($destroyedRoomIds))
                ->getQuery()
                ->getResult();
            foreach ($startResults as $row) {
                if (!isset($roomStarts[$row['roomId']])) {
                    $roomStarts[$row['roomId']] = $row['startUtc'];
                }
            }
        }

        $result = [];
        foreach ($roomIds as $roomId) {
            if (isset($roomsWithActiveStatus[$roomId])) {
                $result[$roomId] = false;
                continue;
            }
            if (!isset($latestDestroyedMap[$roomId]) || !isset($roomStarts[$roomId])) {
                continue;
            }
            $result[$roomId] = $latestDestroyedMap[$roomId] > $roomStarts[$roomId];
        }
        return $result;
    }
}
