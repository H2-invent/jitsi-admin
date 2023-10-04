<?php

namespace App\Repository;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Service\TimeZoneService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function Doctrine\ORM\QueryBuilder;
use function PHPUnit\Framework\returnArgument;
use function Symfony\Component\DependencyInjection\Loader\Configurator\expr;

/**
 * @method Rooms|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rooms|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rooms[]    findAll()
 * @method Rooms[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoomsRepository extends ServiceEntityRepository
{
    private $timeZoneService;
    private $amountperLayz = 8;

    public function __construct(ManagerRegistry $registry, TimeZoneService $timeZoneService)
    {
        parent::__construct($registry, Rooms::class);
        $this->timeZoneService = $timeZoneService;
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
        $now = new \DateTime('now', $this->timeZoneService->getTimeZone($user));
        $now->setTimezone(new \DateTimeZone('utc'));
        $qb = $this->createQueryBuilder('r');
        return $qb->innerJoin('r.user', 'user')
            ->leftJoin('user.managerElement', 'managerelement')
            ->leftJoin('managerelement.deputy', 'deputy')
            ->andWhere(
                $qb->expr()->orX(
                    'user = :user',
                    'deputy = :user'
                )
            )
            ->andWhere('r.endDateUtc > :now')
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('r.scheduleMeeting'), 'r.scheduleMeeting = false'))
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('r.persistantRoom'), 'r.persistantRoom = false'))
            ->setParameter('now', $now)
            ->setParameter('user', $user)
            ->orderBy('r.startUtc', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findRoomsInPast(User $user, $offset)
    {
        $now = new \DateTime('now', $this->timeZoneService->getTimeZone($user));
        $now->setTimezone(new \DateTimeZone('utc'));
        $qb = $this->createQueryBuilder('r');
        return $qb->innerJoin('r.user', 'user')
            ->leftJoin('user.managerElement', 'managerelement')
            ->leftJoin('managerelement.deputy', 'deputy')
            ->andWhere(
                $qb->expr()->orX(
                    'user = :user',
                    'deputy = :user'
                )
            )
            ->andWhere('r.endDateUtc < :now')
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('r.scheduleMeeting'), 'r.scheduleMeeting = false'))
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('r.persistantRoom'), 'r.persistantRoom = false'))
            ->setParameter('now', $now)
            ->setParameter('user', $user)
            ->orderBy('r.start', 'DESC')
            ->setMaxResults($this->amountperLayz)
            ->setFirstResult($this->amountperLayz * $offset)
            ->getQuery()
            ->getResult();
    }

    public function findRoomsForUser(User $user)
    {
        $now = new \DateTime();
        $qb = $this->createQueryBuilder('r');
        return $qb->innerJoin('r.user', 'user')
            ->leftJoin('user.managerElement', 'managerelement')
            ->leftJoin('managerelement.deputy', 'deputy')
            ->andWhere(
                $qb->expr()->orX(
                    'user = :user',
                    'deputy = :user'
                )
            )
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('r.scheduleMeeting'), 'r.scheduleMeeting = false'))
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('r.persistantRoom'), 'r.persistantRoom = false'))
            ->setParameter('user', $user)
            ->orderBy('r.start', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findRuningRooms(User $user)
    {

        $now = new \DateTime('now', $this->timeZoneService->getTimeZone($user));
        $now->setTimezone(new \DateTimeZone('utc'));
        $qb = $this->createQueryBuilder('r');
        return $qb->innerJoin('r.user', 'user')
            ->leftJoin('user.managerElement', 'managerelement')
            ->leftJoin('managerelement.deputy', 'deputy')
            ->andWhere(
                $qb->expr()->orX(
                    'user = :user',
                    'deputy = :user'
                )
            )
            ->andWhere('r.endDateUtc > :now')
            ->andWhere('r.startUtc < :now')
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('r.scheduleMeeting'), 'r.scheduleMeeting = false'))
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('r.persistantRoom'), 'r.persistantRoom = false'))
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
        $midnight->setTime(23, 59, 59);
        $qb = $this->createQueryBuilder('r');

        return $qb
            ->innerJoin('r.user', 'user')
            ->leftJoin('user.managerElement', 'managerelement')
            ->leftJoin('managerelement.deputy', 'deputy')
            ->andWhere(
                $qb->expr()->orX(
                    'user = :user',
                    'deputy = :user'
                )
            )
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('r.scheduleMeeting'), 'r.scheduleMeeting = false'))
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('r.persistantRoom'), 'r.persistantRoom = false'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->between('r.enddate', ':now', ':midnight'),
                    $qb->expr()->between('r.start', ':now', ':midnight'),
                    $qb->expr()->andX(
                        $qb->expr()->gte('r.enddate', ':midnight'),
                        $qb->expr()->lte('r.start', ':now')
                    )
                )
            )
            ->setParameter('now', $now)
            ->setParameter('midnight', $midnight)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function getMyScheduledRooms(User $user)
    {
        $qb = $this->createQueryBuilder('rooms');
        $qb->innerJoin('rooms.user', 'user')
            ->leftJoin('rooms.moderator', 'moderator')
            ->leftJoin('moderator.managerElement', 'managerelement')
            ->leftJoin('managerelement.deputy', 'deputy')
            ->andWhere(
                $qb->expr()->orX(
                    'user = :user',
                    $qb->expr()->andX(
                        'deputy = :user',
                        'rooms.creator != rooms.moderator'
                    )
                )
            )
            ->setParameter('user', $user)
            ->andWhere('rooms.scheduleMeeting = true');
        $query =  $qb->getQuery();
        return $query->getResult();
    }

     /**
      * @return Rooms[] Returns an array of Rooms objects
      */
    public function getMyPersistantRooms(User $user, $offset)
    {
        $qb = $this->createQueryBuilder('rooms');
        $qb->innerJoin('rooms.user', 'user')
            ->leftJoin('rooms.moderator', 'moderator')
            ->leftJoin('moderator.managerElement', 'managerelement')
            ->leftJoin('managerelement.deputy', 'deputy')
            ->andWhere(
                $qb->expr()->orX(
                    'user = :user',
                    $qb->expr()->andX(
                        'deputy = :user',
                        'rooms.creator != rooms.moderator'
                    )
                )
            )
            ->setParameter('user', $user)
            ->andWhere('rooms.persistantRoom = true');
        return $qb->getQuery()->getResult();
    }

    public function findRoomsFutureAndPast(User $user, $timeBack)
    {
        $now = (new \DateTime())->modify($timeBack);
        $qb = $this->createQueryBuilder('r');
        return $qb->innerJoin('r.user', 'user')

            ->andWhere('user = :user')
            ->andWhere('r.enddate > :now')
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('r.scheduleMeeting'), 'r.scheduleMeeting = false'))
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('r.persistantRoom'), 'r.persistantRoom = false'))
            ->setParameter('now', $now)
            ->setParameter('user', $user)
            ->orderBy('r.start', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findFavoriteRooms(User $user)
    {
        $qb = $this->createQueryBuilder('r');
        return $qb->innerJoin('r.favoriteUsers', 'user')
            ->andWhere(
                $qb->expr()->orX(
                    'user = :user',
                )
            )
            ->setParameter('user', $user)
            ->addSelect('CASE WHEN r.start IS NULL THEN 1 ELSE 0 END as HIDDEN list_order_is_null')
            ->addOrderBy('list_order_is_null', 'DESC') // always ASC
            ->addOrderBy('r.start', 'ASC') //DESC or ASC
            ->getQuery()
            ->getResult();
    }

    public function findFutureRoomsWithNoCallerId($now)
    {
        $qb = $this->createQueryBuilder('r');
        return $qb->leftJoin('r.callerRoom', 'callerRoom')
            ->andWhere($qb->expr()->isNull('callerRoom'))
            ->andWhere($qb->expr()->isNotNull('r.moderator'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->gte('r.endTimestamp', ':now'),
                    $qb->expr()->eq('r.persistantRoom', ':true')
                )
            )
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('r.scheduleMeeting'), 'r.scheduleMeeting = false'))
            ->setParameter('now', $now)
            ->setParameter('true', true)
            ->getQuery()
            ->getResult();
    }

    public function findRoomsnotInPast()
    {
        $now = (new \DateTime('now'))->getTimestamp();
        $qb = $this->createQueryBuilder('r');
        return $qb
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->gte('r.endTimestamp', ':now'),
                    $qb->expr()->eq('r.persistantRoom', ':true')
                )
            )
            ->andWhere($qb->expr()->isNotNull('r.moderator'))
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('r.scheduleMeeting'), 'r.scheduleMeeting = :false'))
            ->setParameter('now', $now)
            ->setParameter('true', true)
            ->setParameter('false', false)
            ->orderBy('r.startUtc', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Rooms[] Returns an array of Rooms objects
     */

    public function findRoomsWithNoTags()
    {
        $qb = $this->createQueryBuilder('r');

        return $qb->andWhere($qb->expr()->isNull('r.tag'))
            ->getQuery()
            ->getResult();
    }

    public function findRoomByCaseInsensitiveUid($value): ?Rooms
    {
        return $this->createQueryBuilder('r')
            ->andWhere('upper(r.uid) = upper(:val)')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Rooms[] Returns an array of Rooms objects
     */

    public function findActualConferenceForServerByStatus(Server $server)
    {
        $qb = $this->createQueryBuilder('r');
        return $qb->innerJoin('r.server', 'server')
            ->innerJoin('r.roomstatuses', 'roomstatuses')
            ->andWhere('roomstatuses.created = :true')
            ->andWhere(
                $qb->expr()->orX(
                    'roomstatuses.destroyed = :false',
                    $qb->expr()->isNull('roomstatuses.destroyed')
                )
            )
            ->andWhere('server = :server')
            ->setParameter('server', $server)
            ->setParameter('false', false)
            ->setParameter('true', true)
            ->getQuery()
            ->getResult();
    }
}
