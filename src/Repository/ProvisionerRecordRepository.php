<?php

namespace App\Repository;

use App\Entity\ProvisionerRecord;
use App\Entity\Rooms;
use App\Service\Provisioner\ProvisionerStatus;
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
                $qb->expr()->in('p.status', ':status')
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
        $nowUtc = new \DateTime('now', new \DateTimeZone('utc'));

        $timeoutThreshold = (new \DateTime())->sub(new \DateInterval("PT5M")); // 5 min timeout to provision server and connect users
        $scheduleThreshold = (new \DateTime())->sub(new \DateInterval("PT{$scheduleMinutesThreshold}M"));

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

//    /**
//     * @return Provisioning[] Returns an array of Provisioning objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Provisioning
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
