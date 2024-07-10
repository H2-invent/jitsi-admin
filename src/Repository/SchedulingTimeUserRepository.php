<?php

namespace App\Repository;

use App\Entity\Rooms;
use App\Entity\SchedulingTimeUser;
use App\Entity\User;
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

     /**
      * @return SchedulingTimeUser[] Returns an array of SchedulingTimeUser objects
      */
    public function findVotesForUserAndRoom(Rooms $rooms, User $user)
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.user', 'u')
            ->andWhere('u = :user')
            ->join('s.scheduleTime', 'time')
            ->innerJoin('time.scheduling','scheduling')
            ->join('scheduling.room', 'r')
            ->andWhere('r = :room')
            ->setParameter('room', $rooms)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }


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
