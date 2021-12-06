<?php

namespace App\Repository;

use App\Entity\LobbyWaitingUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LobbyWaitingUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method LobbyWaitingUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method LobbyWaitingUser[]    findAll()
 * @method LobbyWaitingUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LobbyWaitingUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LobbyWaitingUser::class);
    }

    // /**
    //  * @return LobbyWaitingUser[] Returns an array of LobbyWaitingUser objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?LobbyWaitingUser
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
