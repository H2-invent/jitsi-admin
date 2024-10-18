<?php

namespace App\Repository;

use App\Entity\CallerId;
use App\Entity\Rooms;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CallerId|null find($id, $lockMode = null, $lockVersion = null)
 * @method CallerId|null findOneBy(array $criteria, array $orderBy = null)
 * @method CallerId[]    findAll()
 * @method CallerId[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
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
    public function findByRoomAndPin(Rooms $rooms, $pin): ?CallerId
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
