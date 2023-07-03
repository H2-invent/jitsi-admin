<?php

namespace App\Repository;

use App\Entity\CallerRoom;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use function Doctrine\ORM\QueryBuilder;

/**
 * @method CallerRoom|null find($id, $lockMode = null, $lockVersion = null)
 * @method CallerRoom|null findOneBy(array $criteria, array $orderBy = null)
 * @method CallerRoom[]    findAll()
 * @method CallerRoom[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CallerRoomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CallerRoom::class);
    }

    // /**
    //  * @return CallerRoom[] Returns an array of CallerRoom objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CallerRoom
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function findPastRoomsWithCallerId($now)
    {
        $qb = $this->createQueryBuilder('c');
        return $qb->innerJoin('c.room', 'room')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->lte('room.endTimestamp', ':now'),
                        $qb->expr()->orX(
                            $qb->expr()->eq("room.persistantRoom", ':false'),
                            $qb->expr()->isNull("room.persistantRoom")
                        )
                    ),
                    $qb->expr()->isNull('room.moderator')
                )
            )
            ->setParameter('now', $now)
            ->setParameter('false', false)
            ->getQuery()
            ->getResult();
    }
}
