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
        $rooms = $qb->select('r')
            ->addSelect('server')
            ->addSelect('tag')
            ->addSelect('moderator')
            ->addSelect('creator')
            ->addSelect('repeater')
            ->addSelect('callerRoom')
            ->addSelect('repeaterProtoype')
            ->innerJoin('r.server', 'server')
            ->leftJoin('r.tag', 'tag')
            ->leftJoin('r.moderator', 'moderator')
            ->leftJoin('r.creator', 'creator')
            ->leftJoin('r.repeater', 'repeater')
            ->leftJoin('r.callerRoom', 'callerRoom')
            ->leftJoin('r.repeaterProtoype', 'repeaterProtoype')
            ->andWhere(
                $qb->expr()->orX(
                    ':user MEMBER OF r.user',
                    $qb->expr()->exists(
                        $this->createQueryBuilder('r_dep')
                            ->select('1')
                            ->join('r_dep.moderator', 'm_dep')
                            ->join('m_dep.managerElement', 'me_dep')
                            ->join('me_dep.deputy', 'd_dep')
                            ->where('r_dep = r')
                            ->andWhere('d_dep = :user')
                    )
                )
            )
            ->andWhere('r.endDateUtc < :now')
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('r.scheduleMeeting'), 'r.scheduleMeeting = false'))
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('r.persistantRoom'), 'r.persistantRoom = false'))
            ->setParameter('now', $now)
            ->setParameter('user', $user)
            ->orderBy('r.startUtc', 'DESC')
            ->setMaxResults($this->amountperLayz)
            ->setFirstResult($this->amountperLayz * $offset)
            ->getQuery()
            ->getResult();

        $this->loadDashboardCollections($rooms);

        return $rooms;
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
            ->orderBy('r.startUtc', 'ASC')
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
            ->orderBy('r.startUtc', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findTodayRooms(User $user)
    {
        $now = new \DateTime('now', new \DateTimeZone('utc'));
        $midnight = new \DateTime('now', new \DateTimeZone('utc'));
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
                    $qb->expr()->between('r.endDateUtc', ':now', ':midnight'),
                    $qb->expr()->between('r.startUtc', ':now', ':midnight'),
                    $qb->expr()->andX(
                        $qb->expr()->gte('r.endDateUtc', ':now'),
                        $qb->expr()->lte('r.startUtc', ':midnight')
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
            ->andWhere('rooms.persistantRoom = true')
            ->setMaxResults($this->amountperLayz)
            ->setFirstResult($this->amountperLayz * $offset);
        return $qb->getQuery()->getResult();
    }

    public function findRoomsFutureAndPast(User $user, $timeBack)
    {
        $now = (new \DateTime('now', new \DateTimeZone('utc')))->modify($timeBack);
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

    public function findRoomsForDashboard(User $user)
    {
        $now = new \DateTime('now', $this->timeZoneService->getTimeZone($user));
        $now->setTimezone(new \DateTimeZone('utc'));

        $qb = $this->createQueryBuilder('r');
        $qb->select('r')
            ->addSelect('server')
            ->addSelect('tag')
            ->addSelect('moderator')
            ->addSelect('creator')
            ->addSelect('repeater')
            ->addSelect('callerRoom')
            ->addSelect('repeaterProtoype')
            ->addSelect('CASE WHEN r.startUtc IS NULL THEN 1 ELSE 0 END as HIDDEN list_order_is_null')
            ->addSelect('CASE WHEN r.persistantRoom = true THEN 1 WHEN r.scheduleMeeting = true THEN 2 ELSE 0 END as HIDDEN list_order_category')
            ->innerJoin('r.server', 'server')
            ->leftJoin('r.tag', 'tag')
            ->leftJoin('r.moderator', 'moderator')
            ->leftJoin('r.creator', 'creator')
            ->leftJoin('r.repeater', 'repeater')
            ->leftJoin('r.callerRoom', 'callerRoom')
            ->leftJoin('r.repeaterProtoype', 'repeaterProtoype');

        $rooms = $qb
            ->andWhere(
                $qb->expr()->orX(
                    ':user MEMBER OF r.user',
                    $qb->expr()->andX(
                        $qb->expr()->exists(
                            $this->createQueryBuilder('r_dep')
                                ->select('1')
                                ->join('r_dep.moderator', 'm_dep')
                                ->join('m_dep.managerElement', 'me_dep')
                                ->join('me_dep.deputy', 'd_dep')
                                ->where('r_dep = r')
                                ->andWhere('d_dep = :user')
                        ),
                        $qb->expr()->orX(
                            'r.creator != r.moderator',
                            $qb->expr()->andX(
                                $qb->expr()->orX(
                                    $qb->expr()->isNull('r.persistantRoom'),
                                    'r.persistantRoom = false'
                                ),
                                $qb->expr()->orX(
                                    $qb->expr()->isNull('r.scheduleMeeting'),
                                    'r.scheduleMeeting = false'
                                )
                            )
                        )
                    )
                )
            )
            ->andWhere(
                $qb->expr()->orX(
                    'r.endDateUtc > :now',
                    'r.persistantRoom = true',
                    'r.scheduleMeeting = true'
                )
            )
            ->setParameter('now', $now)
            ->setParameter('user', $user)
            ->addOrderBy('list_order_category', 'ASC')
            ->addOrderBy('list_order_is_null', 'DESC')
            ->addOrderBy('r.startUtc', 'ASC')
            ->getQuery()
            ->getResult();

        $this->loadDashboardCollections($rooms);

        return $rooms;
    }

    public function findFavoriteRooms(User $user)
    {
        $qb = $this->createQueryBuilder('r');
        $rooms = $qb->select('r')
            ->addSelect('server')
            ->addSelect('tag')
            ->addSelect('moderator')
            ->addSelect('creator')
            ->addSelect('repeater')
            ->addSelect('callerRoom')
            ->addSelect('repeaterProtoype')
            ->addSelect('CASE WHEN r.startUtc IS NULL THEN 1 ELSE 0 END as HIDDEN list_order_is_null')
            ->innerJoin('r.server', 'server')
            ->leftJoin('r.tag', 'tag')
            ->leftJoin('r.moderator', 'moderator')
            ->leftJoin('r.creator', 'creator')
            ->leftJoin('r.repeater', 'repeater')
            ->leftJoin('r.callerRoom', 'callerRoom')
            ->leftJoin('r.repeaterProtoype', 'repeaterProtoype')
            ->andWhere(':user MEMBER OF r.favoriteUsers')
            ->setParameter('user', $user)
            ->addOrderBy('list_order_is_null', 'DESC')
            ->addOrderBy('r.startUtc', 'ASC')
            ->getQuery()
            ->getResult();

        $this->loadDashboardCollections($rooms);

        return $rooms;
    }

    /**
     * Pre-loads the to-many collections that the dashboard templates access (participants,
     * schedulings, uploaded recordings and the moderator's deputy list) so that touching
     * them afterwards (e.g. $room->getUser(), $room->schedulings[0]) does not trigger a
     * lazy-load query per room (the N+1 problem).
     *
     * How it works:
     *
     * The $rooms passed here are the entities just returned by one of the find*() methods
     * of this repository and are therefore still managed by the EntityManager. Within a
     * single unit of work (one request), Doctrine's identity map guarantees that there is
     * exactly one object instance per entity primary key, so any query that hydrates a
     * Rooms entity whose id is in $ids reuses the exact same instance instead of creating
     * a second one.
     *
     * Each query below is a fetch-join, e.g.:
     *
     *     SELECT r, u FROM Rooms r LEFT JOIN r.user u WHERE r.id IN (:ids)
     *
     * When Doctrine hydrates such a query it reuses the already-managed Rooms instances
     * from the identity map and, as a side effect of the fetch-join, initializes the
     * corresponding collection (here r.user) with the joined related entities and marks
     * it as initialized. A later $room->getUser() therefore returns the already-loaded
     * collection without issuing any additional SQL.
     *
     * The return value of getResult() is deliberately ignored: the queries exist for that
     * hydration side effect on the managed instances, not for their result set (which
     * merely contains the same Rooms instances again).
     *
     * One query is issued per association and each uses an IN() clause over the room ids,
     * so the number of queries is constant no matter how many rooms are loaded (no N+1),
     * and no single query joins more than one to-many association (no cartesian product /
     * row explosion). For the same reason the main find*() queries in this repository only
     * fetch-join to-one associations and leave the to-many ones to this method.
     *
     * @param Rooms[] $rooms
     */
    private function loadDashboardCollections(array $rooms): void
    {
        if (empty($rooms)) {
            return;
        }

        $ids = array_values(array_unique(array_map(static fn(Rooms $room) => $room->getId(), $rooms)));
        $em = $this->getEntityManager();

        // participants (r.user, ManyToMany)
        $em->createQueryBuilder()
            ->select('r', 'u')
            ->from(Rooms::class, 'r')
            ->leftJoin('r.user', 'u')
            ->where('r.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        // schedulings (OneToMany)
        $em->createQueryBuilder()
            ->select('r', 's')
            ->from(Rooms::class, 'r')
            ->leftJoin('r.schedulings', 's')
            ->where('r.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        // uploadedRecordings (OneToMany)
        $em->createQueryBuilder()
            ->select('r', 'rec')
            ->from(Rooms::class, 'r')
            ->leftJoin('r.uploadedRecordings', 'rec')
            ->where('r.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        // moderator deputy collection (moderator.managerElement + deputy)
        $em->createQueryBuilder()
            ->select('r', 'm', 'me', 'd')
            ->from(Rooms::class, 'r')
            ->leftJoin('r.moderator', 'm')
            ->leftJoin('m.managerElement', 'me')
            ->leftJoin('me.deputy', 'd')
            ->where('r.id IN (:ids)')
            ->setParameter('ids', $ids)
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
    public function findRoomsForRoomInGivenMinutes(Server $server, $minutes = 0)
    {
        $qb = $this->createQueryBuilder('r');

        return $qb->leftJoin('r.server', 'server')
            ->andWhere('server = :server')
            ->andWhere('r.startUtc BETWEEN :now AND :future')
            ->setParameter('server', $server)
            ->setParameter('now', new \DateTime('now', new \DateTimeZone('utc')))
            ->setParameter('future', new \DateTime("+$minutes minutes", new \DateTimeZone('utc')))
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

    public function countUsersForServer(Server $server): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(u.id)')
            ->innerJoin('r.user', 'u')
            ->where('r.server = :server')
            ->setParameter('server', $server)
            ->getQuery()
            ->getSingleScalarResult();
    }

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
