<?php

namespace App\Repository;

use App\Entity\RoomsUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RoomsUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method RoomsUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method RoomsUser[]    findAll()
 * @method RoomsUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoomsUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RoomsUser::class);
    }

    // /**
    //  * @return RoomsUser[] Returns an array of RoomsUser objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RoomsUser
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
