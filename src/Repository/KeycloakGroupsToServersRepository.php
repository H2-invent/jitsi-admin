<?php

namespace App\Repository;

use App\Entity\KeycloakGroupsToServers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method KeycloakGroupsToServers|null find($id, $lockMode = null, $lockVersion = null)
 * @method KeycloakGroupsToServers|null findOneBy(array $criteria, array $orderBy = null)
 * @method KeycloakGroupsToServers[]    findAll()
 * @method KeycloakGroupsToServers[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class KeycloakGroupsToServersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KeycloakGroupsToServers::class);
    }

    // /**
    //  * @return KeycloakGroupsToServers[] Returns an array of KeycloakGroupsToServers objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('k')
            ->andWhere('k.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('k.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?KeycloakGroupsToServers
    {
        return $this->createQueryBuilder('k')
            ->andWhere('k.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
