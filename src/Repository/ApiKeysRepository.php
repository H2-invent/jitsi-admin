<?php

namespace App\Repository;

use App\Entity\ApiKeys;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ApiKeys|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApiKeys|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApiKeys[]    findAll()
 * @method ApiKeys[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApiKeysRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiKeys::class);
    }

    // /**
    //  * @return ApiKeys[] Returns an array of ApiKeys objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ApiKeys
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
