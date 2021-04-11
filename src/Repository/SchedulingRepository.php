<?php

namespace App\Repository;

use App\Entity\Scheduling;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Scheduling|null find($id, $lockMode = null, $lockVersion = null)
 * @method Scheduling|null findOneBy(array $criteria, array $orderBy = null)
 * @method Scheduling[]    findAll()
 * @method Scheduling[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SchedulingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Scheduling::class);
    }

    // /**
    //  * @return Scheduling[] Returns an array of Scheduling objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Scheduling
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
