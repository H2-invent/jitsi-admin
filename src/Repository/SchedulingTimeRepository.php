<?php

namespace App\Repository;

use App\Entity\Rooms;
use App\Entity\Scheduling;
use App\Entity\SchedulingTime;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SchedulingTime|null find($id, $lockMode = null, $lockVersion = null)
 * @method SchedulingTime|null findOneBy(array $criteria, array $orderBy = null)
 * @method SchedulingTime[]    findAll()
 * @method SchedulingTime[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SchedulingTimeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SchedulingTime::class);
    }

    // /**
    //  * @return SchedulingTime[] Returns an array of SchedulingTime objects
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
    public function findOneBySomeField($value): ?SchedulingTime
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

     /**
      * @return SchedulingTime[] Returns an array of SchedulingTime objects
      */
    public function findSchedulingTimeForUserAndScheduling(Scheduling $scheduling, User $user)
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.scheduling','scheduling')
            ->andWhere('scheduling =:scheduling')
            ->setParameter('scheduling', $scheduling)
            ->innerJoin('s.schedulingTimeUsers','schedulingTimeUsers')
            ->innerJoin('schedulingTimeUsers.user', 'user')
            ->andWhere('user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult()
        ;
    }

}
