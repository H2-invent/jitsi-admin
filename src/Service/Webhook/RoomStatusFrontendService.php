<?php

namespace App\Service\Webhook;

use App\Entity\Rooms;
use App\Entity\RoomStatus;
use App\Entity\RoomStatusParticipant;
use App\Repository\RoomStatusParticipantRepository;
use App\Repository\RoomStatusRepository;
use Doctrine\ORM\EntityManagerInterface;

class RoomStatusFrontendService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function isRoomCreated(Rooms $rooms): bool
    {
        /** @var RoomStatusRepository $repository */
        $repository = $this->em->getRepository(RoomStatus::class);
        $roomStatus = $repository->findCreatedRooms($rooms);
        if ($roomStatus) {
            return true;
        }

        return false;
    }

    /**
     * @return RoomStatusParticipant[]
     */
    public function numberOfOccupants(Rooms $rooms): array
    {
        /** @var RoomStatusParticipantRepository $repository */
        $repository = $this->em->getRepository(RoomStatusParticipant::class);
        $parts      = $repository->findOccupantsOfRoom($rooms);

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

        return array_any($status, fn($data) => $data->getDestroyedUtc() > $rooms->getStartUtc());
    }

    /**
     * @param array<int|string, int|null> $roomIds
     * @return array<int, bool>
     */
    public function getRoomHasStatusMap(array $roomIds): array
    {
        if (empty($roomIds)) {
            return [];
        }

        $qb       = $this->em->getRepository(RoomStatus::class)->createQueryBuilder('rs');
        $statuses = $qb->select('DISTINCT IDENTITY(rs.room) as roomId')
            ->where($qb->expr()->in('rs.room', ':roomIds'))
            ->setParameter('roomIds', $roomIds)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($statuses as $status) {
            $result[(int) $status['roomId']] = true;
        }

        return $result;
    }

    /**
     * @param array<int|string, int|null> $roomIds
     * @return array<int, bool>
     */
    public function getRoomCreatedStatusMap(array $roomIds): array
    {
        if (empty($roomIds)) {
            return [];
        }

        $qb       = $this->em->getRepository(RoomStatus::class)->createQueryBuilder('rs');
        $statuses = $qb->select('IDENTITY(rs.room) as roomId')
            ->where($qb->expr()->in('rs.room', ':roomIds'))
            ->andWhere($qb->expr()->isNull('rs.destroyed'))
            ->setParameter('roomIds', $roomIds)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($statuses as $status) {
            $result[(int) $status['roomId']] = true;
        }

        return $result;
    }

    /**
     * @param array<int|string, int|null> $roomIds
     * @return array<int, array<int, string>>
     */
    public function getRoomOccupantsMap(array $roomIds): array
    {
        if (empty($roomIds)) {
            return [];
        }

        $qb        = $this->em->getRepository(RoomStatusParticipant::class)->createQueryBuilder('rp');
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
            $result[(int) $o['roomId']][] = $o['participantName'];
        }

        return $result;
    }

    /**
     * @param array<int|string, int|null> $roomIds
     * @return array<int|string, bool>
     */
    public function getRoomClosedStatusMap(array $roomIds): array
    {
        if (empty($roomIds)) {
            return [];
        }

        $qb     = $this->em->getRepository(RoomStatus::class)->createQueryBuilder('rs');
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

        $destroyedRoomIds   = [];
        $latestDestroyedMap = [];
        foreach ($destroyed as $row) {
            $destroyedRoomIds[]                 = $row['roomId'];
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
                $roomStarts[$row['roomId']] ??= $row['startUtc'];
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
            $destroyedTs     = strtotime($latestDestroyedMap[$roomId]);
            $startTs         = $roomStarts[$roomId]->getTimestamp();
            $result[$roomId] = $destroyedTs > $startTs;
        }

        return $result;
    }
}
