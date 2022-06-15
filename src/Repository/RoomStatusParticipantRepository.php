<?php

namespace App\Repository;

use App\Entity\Rooms;
use App\Entity\RoomStatusParticipant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use function Doctrine\ORM\QueryBuilder;

/**
 * @method RoomStatusParticipant|null find($id, $lockMode = null, $lockVersion = null)
 * @method RoomStatusParticipant|null findOneBy(array $criteria, array $orderBy = null)
 * @method RoomStatusParticipant[]    findAll()
 * @method RoomStatusParticipant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoomStatusParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RoomStatusParticipant::class);
    }

    // /**
    //  * @return RoomStatusParticipant[] Returns an array of RoomStatusParticipant objects
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
    public function findOneBySomeField($value): ?RoomStatusParticipant
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function findOccupantsOfRoom(Rooms $rooms)
    {
        $qb =  $this->createQueryBuilder('r');

           return $qb->innerJoin('r.roomStatus','roomStatus')
            ->innerJoin('roomStatus.room','room')
            ->andWhere('room = :room')
            ->andWhere('r.inRoom = true')
            ->andWhere($qb->expr()->isNull('roomStatus.destroyed'))
            ->setParameter('room', $rooms)
            ->getQuery()
            ->getResult()
            ;
    }
}
