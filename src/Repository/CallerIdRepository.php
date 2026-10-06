<?php

namespace App\Repository;

use App\Entity\CallerId;
use App\Entity\Rooms;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CallerId>
 */
class CallerIdRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CallerId::class);
    }

    // /**
    //  * @return CallerId[] Returns an array of CallerId objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CallerId
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function findByRoomAndPin(Rooms $rooms, string $pin): ?CallerId
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.room', 'room')
            ->andWhere('room = :room')
            ->andWhere('c.callerId = :pin')
            ->setParameter('pin', $pin)
            ->setParameter('room', $rooms)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
