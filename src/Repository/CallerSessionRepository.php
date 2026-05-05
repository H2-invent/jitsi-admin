<?php

namespace App\Repository;

use App\Entity\CallerSession;
use App\Entity\Rooms;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CallerSession|null find($id, $lockMode = null, $lockVersion = null)
 * @method CallerSession|null findOneBy(array $criteria, array $orderBy = null)
 * @method CallerSession[]    findAll()
 * @method CallerSession[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CallerSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CallerSession::class);
    }

    /**
     * @return CallerSession[] Returns an array of CallerSession objects
     */
    public function findCallerSessionsByRoom(Rooms $rooms)
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.caller', 'caller')
            ->innerJoin('caller.room', 'room')
            ->andWhere('room = :room')
            ->setParameter('room', $rooms)
            ->getQuery()
            ->getResult();
    }


public function findCallerSessionByUserAndRoom(User $user, Rooms $rooms): ?CallerSession
{
    return $this->createQueryBuilder('c')
        ->innerJoin('c.caller','caller')
        ->innerJoin('caller.user', 'user')
        ->innerJoin('caller.room','room')
        ->andWhere('user = :user')
        ->setParameter('user',$user)
        ->andWhere('room = :room')
        ->setParameter('room', $rooms)
        ->getQuery()
        ->getOneOrNullResult()
    ;
}

    // /**
    //  * @return CallerSession[] Returns an array of CallerSession objects
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
    public function findOneBySomeField($value): ?CallerSession
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }*/
}
