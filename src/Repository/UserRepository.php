<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use function Doctrine\ORM\QueryBuilder;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    // /**
    //  * @return Server[] Returns an array of Server objects
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
    */

    /*
    public function findOneBySomeField($value): ?Server
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function findOneByEmail($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findMyUserByEmail($value, User $user)
    {
        $qb = $this->createQueryBuilder('u')
            ->innerJoin(' u.addressbookInverse', 'user')
            ->andWhere('user = :user')
            ->setParameter('user', $user);

        return $qb->andWhere($qb->expr()->orX()->addMultiple(
            [
                $qb->expr()->like('u.email', ':search'),
                $qb->expr()->like('u.firstName', ':search'),
                $qb->expr()->like('u.lastName', ':search'),
                $qb->expr()->like('u.username', ':search'),
                $qb->expr()->like('u.spezialProperties', ':search'),
            ]
        ))
            ->setParameter('search', '%' . $value . '%')
            ->getQuery()
            ->getResult();

    }

    public function findUsersByLdapServerId($value)
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.ldapUserProperties', 'ldap_user_properties')
            ->andWhere('ldap_user_properties.ldapNumber = :serverId')
            ->setParameter('serverId', $value)
            ->getQuery()
            ->getResult();
    }

    public function findUsersfromLdapService()
    {
        $qb = $this->createQueryBuilder('u');

        return $qb->join('u.ldapUserProperties', 'ldap_user_properties')
            ->andWhere($qb->expr()->isNotNull('ldap_user_properties'))
            ->getQuery()
            ->getResult();
    }
    public function findUsersfromLdapdn($userDn)
    {
        $qb = $this->createQueryBuilder('u');

        return $qb->join('u.ldapUserProperties', 'ldap_user_properties')
            ->andWhere('ldap_user_properties.ldapDn = :ldapdn')
            ->setParameter('ldapdn',$userDn)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
