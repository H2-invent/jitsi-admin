<?php

namespace App\Repository;

use App\Entity\Rooms;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use function Doctrine\ORM\QueryBuilder;

/**
 * @method Rooms|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rooms|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rooms[]    findAll()
 * @method Rooms[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoomsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rooms::class);
    }

    // /**
    //  * @return Rooms[] Returns an array of Rooms objects
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
    public function findOneBySomeField($value): ?Rooms
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function findRoomsInFuture(User $user)
    {
        $now = new \DateTime();
        return $this->createQueryBuilder('r')
            ->innerJoin('r.user','user')
            ->andWhere('user = :user')
            ->andWhere('r.enddate > :now')
            ->setParameter('now', $now)
            ->setParameter('user', $user)
            ->orderBy('r.start', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }
    public function findRoomsInPast(User $user)
    {
        $now = new \DateTime();
        return $this->createQueryBuilder('r')
            ->innerJoin('r.user','user')
            ->andWhere('user = :user')
            ->andWhere('r.enddate < :now')
            ->setParameter('now', $now)
            ->setParameter('user', $user)
            ->orderBy('r.start', 'DESC')
            ->getQuery()
            ->getResult()
            ;
    }
    public function findRoomsForUser(User $user)
    {
        $now = new \DateTime();
        return $this->createQueryBuilder('r')
            ->innerJoin('r.user','user')
            ->andWhere('user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.start', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }
    public function findRuningRooms(User $user)
    {
        $now = new \DateTime();
        return $this->createQueryBuilder('r')
            ->innerJoin('r.user','user')
            ->andWhere('user = :user')
            ->andWhere('r.enddate > :now')
            ->andWhere('r.start < :now')
            ->setParameter('now', $now)
            ->setParameter('user', $user)
            ->orderBy('r.start', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findTodayRooms(User $user)
    {
        $now = new \DateTime();
        $midnight = new \DateTime();
        $midnight->setTime(23, 59,59);
        $qb = $this->createQueryBuilder('r');

        return $qb
            ->innerJoin('r.user','user')
            ->andWhere('user = :user')
            ->andWhere($qb->expr()->orX(
                $qb->expr()->between('r.enddate',':now',':midnight'),
                $qb->expr()->between('r.start',':now',':midnight'),
                $qb->expr()->andX(
                    $qb->expr()->gte('r.enddate',':midnight'),
                    $qb->expr()->lte('r.start',':now')
                )
            ))
            ->setParameter('now', $now)
            ->setParameter('midnight', $midnight)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}
