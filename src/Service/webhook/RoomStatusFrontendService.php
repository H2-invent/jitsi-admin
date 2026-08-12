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
        $statuses = $qb->select(
                    'IDENTITY(rs.room) as roomId',
                    'rs.destroyed',
                    'rs.destroyedAt',
                    'room.startUtc as roomStartUtc'
                )
            ->innerJoin('rs.room', 'room')
            ->where($qb->expr()->in('rs.room', ':roomIds'))
            ->setParameter('roomIds', $roomIds)
            ->getQuery()
            ->getResult();

        $roomStatuses = [];
        foreach ($statuses as $row) {
            $roomStatuses[$row['roomId']][] = $row;
        }

        $result = [];
        foreach ($roomStatuses as $roomId => $rows) {
            $allDestroyed = true;
            foreach ($rows as $row) {
                if ($row['destroyed'] !== true) {
                    $allDestroyed = false;
                    break;
                }
            }
            if (!$allDestroyed) {
                $result[$roomId] = false;
                continue;
            }
            $hasValidClose = false;
            foreach ($rows as $row) {
                if ($row['destroyedAt'] && $row['roomStartUtc'] && $row['destroyedAt'] > $row['roomStartUtc']) {
                    $hasValidClose = true;
                    break;
                }
            }
            $result[$roomId] = $hasValidClose;
        }
        return $result;
    }
}
