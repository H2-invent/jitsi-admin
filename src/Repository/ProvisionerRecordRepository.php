<?php

namespace App\Repository;

use App\Entity\ProvisionerRecord;
use App\Entity\Rooms;
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

        $timeoutThreshold = $now->sub(new \DateInterval("PT5M")); // 5 min timeout to provision server and connect users
        $scheduleThreshold = $now->sub(new \DateInterval("PT{$scheduleMinutesThreshold}M"));

        $qb = $this->createQueryBuilder('provision');
        return $qb
            ->select('provision', 'room')
            ->innerJoin('provision.room', 'room')
            ->innerJoin('room.server', 'server')
            ->leftJoin('room.roomstatuses', 'status')
            ->leftJoin('status.roomStatusParticipants', 'status_participant')
            ->leftJoin('room.liveKitRecordings', 'recording')

            ->andWhere('provision.status = :runningProvisionStatus')
            ->andWhere('room.originalServer IS NOT NULL')
            ->andWhere('server.isAllowedToCloneForAutoscale IS NULL')
            ->andWhere('server.isProvisioningEnabled = true')
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
                $qb->expr()->orX(
                    'status_participant.inRoom IS NULL',
                    'status_participant.inRoom = false',
                    'status.destroyed = true',
                ),
            )
            ->andWhere(
                $qb->expr()->orX(
                    'recording.id IS NULL',
                    'recording.user IS NULL',
                ),
            )
            ->andWhere(
                'room.createdAt < :timeoutThreshold'
            )
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
