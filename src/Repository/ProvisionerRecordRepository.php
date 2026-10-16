<?php

namespace App\Repository;

use App\Entity\ProvisionerRecord;
use App\Entity\Recording;
use App\Entity\Rooms;
use App\Entity\RoomStatus;
use App\Message\Provisioner\Enum\Status;
use App\Service\Provisioner\ProvisionerStatus;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProvisionerRecord>
 */
class ProvisionerRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProvisionerRecord::class);
    }

    /**
     * @param ProvisionerStatus[] $statuses
     */
    public function findByStatuses(Rooms $room, array $statuses): array
    {
        $qb = $this->createQueryBuilder('p');
        return $qb
            ->andWhere('p.room = :room')
            ->andWhere(
                $qb->expr()->in('p.status', ':statuses')
            )
            ->setParameter('statuses', $statuses)
            ->setParameter('room', $room)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * finds ProvisionerRecords whose room's provisioned server can be deleted, i.e. rooms which are:
     * (  permanent Conferences
     * OR planned Conferences whose endDate has passed )
     * AND has provision currently running (in PROVISION_DONE state)
     * AND all participants left
     * AND no more recording active
     *
     * @return ProvisionerRecord[]
     */
    public function findProvisioningRecordsWhoseServerCanBeDeleted(int $scheduleMinutesThreshold): array
    {
        $nowUtc = new \DateTimeImmutable('now', new \DateTimeZone('utc'));
        $now = new \DateTimeImmutable('now');

        $timeoutThreshold = $now->sub(new \DateInterval("PT5M"));
        $scheduleThreshold = $now->sub(new \DateInterval("PT{$scheduleMinutesThreshold}M"));

        $qb = $this->createQueryBuilder('provision');

        // a participant that is still in the room, in a status that is NOT destroyed
        $activeParticipantSub = $this->getEntityManager()->createQueryBuilder()
            ->select('1')
            ->from(RoomStatus::class, 'sub_status')
            ->innerJoin('sub_status.roomStatusParticipants', 'sub_participant')
            ->where('sub_status.room = room')
            ->andWhere('sub_participant.inRoom = true')
            ->andWhere('sub_status.destroyed = false OR sub_status.destroyed IS NULL')
            ->getDQL();

        // a recording that is still running (has a user)
        $activeRecordingSub = $this->getEntityManager()->createQueryBuilder()
            ->select('1')
            ->from(Recording::class, 'sub_recording')
            ->where('sub_recording.room = room')
            ->andWhere('sub_recording.user IS NOT NULL')
            ->getDQL();

        return $qb
            ->select('provision', 'room')
            ->innerJoin('provision.room', 'room')
            ->innerJoin('room.server', 'server')

            ->andWhere('provision.status = :runningProvisionStatus')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        'room.persistantRoom = true',
                        'room.createdAt < :scheduleThreshold'
                    ),
                    $qb->expr()->andX(
                        'room.persistantRoom = false',
                        'room.endDateUtc < :nowUtc',
                    ),
                ),
            )
            ->andWhere(
                $qb->expr()->not(
                    $qb->expr()->exists($activeParticipantSub)
                )
            )
            ->andWhere(
                $qb->expr()->not(
                    $qb->expr()->exists($activeRecordingSub)
                )
            )
            ->andWhere('room.createdAt < :timeoutThreshold')

            ->setParameter('nowUtc', $nowUtc)
            ->setParameter('timeoutThreshold', $timeoutThreshold)
            ->setParameter('scheduleThreshold', $scheduleThreshold)
            ->setParameter('runningProvisionStatus', ProvisionerStatus::PROVISION_DONE)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return ProvisionerRecord[]
     */
    public function findStuckProvisions(): array
    {
        $now = new DateTimeImmutable('now');
        $timeoutThreshold = $now->sub(new \DateInterval("PT1M")); // 1 min timeout for the provisions

        $qb = $this->createQueryBuilder('p');
        return $qb
            ->andWhere('p.status = :provisioned_status')
            ->andWhere('p.updatedAt < :timeout_threshold')
            ->setParameter('provisioned_status', ProvisionerStatus::PROVISION_REQUESTED)
            ->setParameter('timeout_threshold', $timeoutThreshold)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return ProvisionerRecord[]
     */
    public function findStuckDeletions(): array
    {
        $now = new DateTimeImmutable('now');
        $timeoutThreshold = $now->sub(new \DateInterval("PT2M")); // 2 min timeout for the deletions

        $qb = $this->createQueryBuilder('p');
        return $qb
            ->andWhere('p.status = :provisioned_status')
            ->andWhere('p.updatedAt < :timeout_threshold')
            ->setParameter('provisioned_status', ProvisionerStatus::DELETION_REQUESTED)
            ->setParameter('timeout_threshold', $timeoutThreshold)
            ->getQuery()
            ->getResult()
        ;
    }
}
