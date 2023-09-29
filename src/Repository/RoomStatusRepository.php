<?php

namespace App\Repository;

use App\Entity\Rooms;
use App\Entity\RoomStatus;
use App\Entity\Server;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function Doctrine\ORM\QueryBuilder;

/**
 * @method RoomStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method RoomStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method RoomStatus[]    findAll()
 * @method RoomStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoomStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RoomStatus::class);
    }

    // /**
    //  * @return RoomStatus[] Returns an array of RoomStatus objects
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
    public function findOneBySomeField($value): ?RoomStatus
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function findCreatedRooms(Rooms $rooms): ?RoomStatus
    {

        $qb =  $this->createQueryBuilder('r');

            return $qb->andWhere($qb->expr()->isNull('r.destroyed'))
                ->innerJoin('r.room', 'room')
            ->andWhere('room =:room')
            ->setParameter('room', $rooms)
            ->getQuery()
            ->getOneOrNullResult();
    }
    public function findCreatedRoomsbyJitsiId($jitsiId): ?RoomStatus
    {
        $id = explode('@', strrev($jitsiId), 2);
        $id = strrev($id[sizeof($id) - 1]);
        $qb = $this->createQueryBuilder('r');

        return $qb->andWhere($qb->expr()->isNull('r.destroyed'))
            ->andWhere('r.jitsiRoomId LIKE :jitsiid')
            ->setParameter('jitsiid', '%' . addcslashes($id, '%_') . '%')
            ->getQuery()
            ->getOneOrNullResult();
    }
    public function findRoomStatusByUid(string $uid):?RoomStatus
    {
        $qb = $this->createQueryBuilder('r');
        return $qb
            ->andWhere('r.jitsiRoomId LIKE :uid')
            ->setParameter('uid', '%' . $uid . '%')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
