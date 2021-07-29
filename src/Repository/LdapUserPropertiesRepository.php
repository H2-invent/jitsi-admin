<?php

namespace App\Repository;

use App\Entity\LdapUserProperties;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LdapUserProperties|null find($id, $lockMode = null, $lockVersion = null)
 * @method LdapUserProperties|null findOneBy(array $criteria, array $orderBy = null)
 * @method LdapUserProperties[]    findAll()
 * @method LdapUserProperties[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LdapUserPropertiesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LdapUserProperties::class);
    }

    // /**
    //  * @return LdapUserProperties[] Returns an array of LdapUserProperties objects
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
    public function findOneBySomeField($value): ?LdapUserProperties
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
