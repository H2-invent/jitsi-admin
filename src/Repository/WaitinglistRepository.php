<?php

namespace App\Repository;

use App\Entity\Waitinglist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Waitinglist|null find($id, $lockMode = null, $lockVersion = null)
 * @method Waitinglist|null findOneBy(array $criteria, array $orderBy = null)
 * @method Waitinglist[]    findAll()
 * @method Waitinglist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WaitinglistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Waitinglist::class);
    }

    // /**
    //  * @return Waitinglist[] Returns an array of Waitinglist objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('w.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Waitinglist
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
