<?php

namespace App\Repository;

use App\Entity\CalloutSession;
use App\Entity\Rooms;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CalloutSession>
 *
 * @method CalloutSession|null find($id, $lockMode = null, $lockVersion = null)
 * @method CalloutSession|null findOneBy(array $criteria, array $orderBy = null)
 * @method CalloutSession[]    findAll()
 * @method CalloutSession[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CalloutSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CalloutSession::class);
    }

    public function add(CalloutSession $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CalloutSession $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return CalloutSession[] Returns an array of CalloutSession objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?CalloutSession
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function findCalloutSessionPoolOrDialing(User $user, Rooms $rooms): ?CalloutSession
    {
        $qb = $this->createQueryBuilder('c');
        return $qb
            ->andWhere('c.room = :room')
            ->andWhere('c.user = :user')
            ->andWhere($qb->expr()->lt(':maxStatus', 'c.status'))
            ->setParameter(':room', $rooms)
            ->setParameter(':user', $user)
            ->setParameter(':maxStatus', CalloutSession::$ON_HOLD)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findCalloutSessionActive($calloutSessionId): ?CalloutSession
    {
        $qb = $this->createQueryBuilder('c');
        return $qb
            ->andWhere('c.uid=:sessionId')
            ->andWhere($qb->expr()->gte('c.state', ':minState'))
            ->andWhere($qb->expr()->lt('c.state', ':maxState'))
            ->setParameter(':maxState', CalloutSession::$ON_HOLD)
            ->setParameter('minState', CalloutSession::$DIALED)
            ->setParameter('sessionId', $calloutSessionId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return CalloutSession[] Returns an array of CalloutSession objects
     */
    public function findonHoldCalloutSessions(): array
    {
        $qb = $this->createQueryBuilder('c');
        return $qb
            ->andWhere($qb->expr()->gte('c.state', ':state'))
            ->setParameter('state', CalloutSession::$ON_HOLD)
            ->getQuery()
            ->getResult();
    }
}
