<?php

namespace App\Repository;

use App\Entity\LobbyWaitungUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LobbyWaitungUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method LobbyWaitungUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method LobbyWaitungUser[]    findAll()
 * @method LobbyWaitungUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LobbyWaitungUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LobbyWaitungUser::class);
    }

    // /**
    //  * @return LobbyWaitungUser[] Returns an array of LobbyWaitungUser objects
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
    public function findOneBySomeField($value): ?LobbyWaitungUser
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function findOldLobbyWaitinguser(\DateTime $oldestDate)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.createdAt < :oldest')
            ->setParameter('oldest', $oldestDate)
            ->getQuery()
            ->getResult();
    }
}
