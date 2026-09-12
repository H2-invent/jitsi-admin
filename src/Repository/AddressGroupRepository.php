<?php

namespace App\Repository;

use App\Entity\AddressGroup;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AddressGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method AddressGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method AddressGroup[]    findAll()
 * @method AddressGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AddressGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AddressGroup::class);
    }

    // /**
    //  * @return Addressgroup[] Returns an array of Addressgroup objects
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
    public function findOneBySomeField($value): ?Addressgroup
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function findMyAddressBookGroupsByName($value, User $user)
    {
        $qb = $this->createQueryBuilder('g')
            ->innerJoin(' g.leader', 'leader')
            ->andWhere('leader = :user')
            ->setParameter('user', $user);

        return $qb->andWhere($qb->expr()->like('g.indexer', ':search'))
            ->setParameter('search', '%' . $value . '%')
            ->getQuery()
            ->getResult();
    }
}
