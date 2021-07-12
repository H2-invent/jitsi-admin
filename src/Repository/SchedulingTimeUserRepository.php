<?php

namespace App\Repository;

use App\Entity\SchedulingTimeUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SchedulingTimeUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method SchedulingTimeUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method SchedulingTimeUser[]    findAll()
 * @method SchedulingTimeUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SchedulingTimeUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SchedulingTimeUser::class);
    }

    // /**
    //  * @return SchedulingTimeUser[] Returns an array of SchedulingTimeUser objects
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
    public function findOneBySomeField($value): ?SchedulingTimeUser
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
