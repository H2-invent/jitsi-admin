<?php

namespace App\Repository;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Service\TimeZoneService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Rooms|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rooms|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rooms[]    findAll()
 * @method Rooms[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoomsRepository extends ServiceEntityRepository
{
    private const PAGE_SIZE = 10;

    private $timeZoneService;

    public function __construct(ManagerRegistry $registry, TimeZoneService $timeZoneService)
    {
        parent::__construct($registry, Rooms::class);
        $this->timeZoneService = $timeZoneService;
    }

    public static function getPageSize(): int
    {
        return self::PAGE_SIZE;
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
    /**
     * Returns a bounded page of upcoming, non-scheduled, non-persistent rooms ordered by
     * start time ascending. Supports keyset (seek) pagination: pass the id of the last room
     * of the previously loaded page to load the next page without a slow OFFSET scan.
     *
     * The result may contain up to PAGE_SIZE + 1 rooms so callers can detect whether a next
     * page exists (count > PAGE_SIZE) without issuing an extra count query.
     *
     * @return Rooms[]
     */
    public function findRoomsInFuture(User $user, ?int $lastRoomId = null): array
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
            ->innerJoin('r.server', 'server')
            ->leftJoin('r.tag', 'tag')
            ->leftJoin('r.moderator', 'moderator')
            ->leftJoin('r.creator', 'creator')
            ->leftJoin('r.repeater', 'repeater')
            ->leftJoin('r.callerRoom', 'callerRoom')
            ->leftJoin('r.repeaterProtoype', 'repeaterProtoype')
            ->andWhere($this->memberOrDeputyIdCondition($qb, 'r', $this->dashboardDeputyRestriction($qb)))
            ->andWhere('r.startUtc IS NOT NULL')
            ->andWhere('r.endDateUtc > :now')
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('r.scheduleMeeting'), 'r.scheduleMeeting = false'))
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('r.persistantRoom'), 'r.persistantRoom = false'))
            ->setParameter('now', $now)
            ->setParameter('user', $user)
            ->addOrderBy('r.startUtc', 'ASC')
            ->addOrderBy('r.id', 'ASC')
            ->setMaxResults(self::PAGE_SIZE + 1);

        $this->applyAscendingSeekCondition($qb, $lastRoomId);

        $rooms = $qb->getQuery()->getResult();

        $this->loadDashboardCollections($rooms);

        return $rooms;
    }

    /**
     * Returns a bounded page of past (non-scheduled, non-persistent) rooms ordered by start
     * time descending. Supports keyset (seek) pagination via $lastRoomId.
     *
     * The result may contain up to PAGE_SIZE + 1 rooms so callers can detect whether a next
     * page exists (count > PAGE_SIZE) without issuing an extra count query.
     *
     * @return Rooms[]
     */
    public function findRoomsInPast(User $user, ?int $lastRoomId = null): array
    {
        $now = new \DateTime('now', $this->timeZoneService->getTimeZone($user));
        $now->setTimezone(new \DateTimeZone('utc'));

        $cursor = $this->resolveCursorRoom($lastRoomId);

        $qb = $this->createQueryBuilder('r');
        $qb->select('r')
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
            ->andWhere($this->memberOrDeputyIdCondition($qb, 'r'))
            ->andWhere('r.endDateUtc < :now')
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('r.scheduleMeeting'), 'r.scheduleMeeting = false'))
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('r.persistantRoom'), 'r.persistantRoom = false'))
            ->setParameter('now', $now)
            ->setParameter('user', $user)
            ->addOrderBy('r.startUtc', 'DESC')
            ->addOrderBy('r.id', 'DESC')
            ->setMaxResults(self::PAGE_SIZE + 1);

        $this->applyDescendingSeekCondition($qb, $cursor);

        $rooms = $qb->getQuery()->getResult();

        $this->loadDashboardCollections($rooms);

        return $rooms;
    }

    /**
     * Returns a bounded page of rooms that are used as schedulers (scheduleMeeting = true).
     * Supports keyset (seek) pagination via $lastRoomId.
     *
     * The result may contain up to PAGE_SIZE + 1 rooms so callers can detect whether a next
     * page exists (count > PAGE_SIZE) without issuing an extra count query.
     *
     * @return Rooms[]
     */
    public function findScheduledRooms(User $user, ?int $lastRoomId = null): array
    {
        $qb = $this->createQueryBuilder('r');
        $qb->select('r')
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
            ->andWhere($this->memberOrDeputyIdCondition($qb, 'r', $this->dashboardDeputyRestriction($qb)))
            ->andWhere('r.scheduleMeeting = true')
            ->setParameter('user', $user)
            ->addOrderBy('r.id', 'DESC')
            ->setMaxResults(self::PAGE_SIZE + 1);

        // Newest schedulers first (id DESC), so keyset pagination simply seeks on the id.
        if ($lastRoomId !== null && $lastRoomId > 0) {
            $qb->andWhere('r.id < :lastId')
                ->setParameter('lastId', $lastRoomId);
        }

        $rooms = $qb->getQuery()->getResult();

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
        return $qb->andWhere($this->memberOrDeputyIdCondition($qb, 'r'))
            ->andWhere('r.endDateUtc > :now')
            ->andWhere('r.startUtc < :now')
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('r.scheduleMeeting'), 'r.scheduleMeeting = false'))
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('r.persistantRoom'), 'r.persistantRoom = false'))
            ->setParameter('now', $now)
            ->setParameter('user', $user)
            ->orderBy('r.startUtc', 'ASC')
            ->setMaxResults(50)
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
            ->andWhere($this->memberOrDeputyIdCondition($qb, 'r'))
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
            ->setMaxResults(50)
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
    public function getMyPersistantRooms(User $user, ?int $lastRoomId = null): array
    {
        $qb = $this->createQueryBuilder('rooms');
        $qb->select('rooms')
            ->addSelect('server')
            ->addSelect('tag')
            ->addSelect('moderator')
            ->addSelect('creator')
            ->addSelect('repeater')
            ->addSelect('callerRoom')
            ->addSelect('repeaterProtoype')
            ->innerJoin('rooms.server', 'server')
            ->leftJoin('rooms.tag', 'tag')
            ->leftJoin('rooms.moderator', 'moderator')
            ->leftJoin('rooms.creator', 'creator')
            ->leftJoin('rooms.repeater', 'repeater')
            ->leftJoin('rooms.callerRoom', 'callerRoom')
            ->leftJoin('rooms.repeaterProtoype', 'repeaterProtoype')
            ->andWhere($this->memberOrDeputyIdCondition($qb, 'rooms', ['r_d.creator != r_d.moderator']))
            ->setParameter('user', $user)
            ->andWhere('rooms.persistantRoom = true')
            ->addOrderBy('rooms.id', 'ASC')
            ->setMaxResults(self::PAGE_SIZE + 1);

        if ($lastRoomId !== null && $lastRoomId > 0) {
            $qb->andWhere('rooms.id > :lastId')
                ->setParameter('lastId', $lastRoomId);
        }

        $rooms = $qb->getQuery()->getResult();

        $this->loadDashboardCollections($rooms);

        return $rooms;
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
            ->andWhere($this->memberOrDeputyIdCondition($qb, 'r', $this->dashboardDeputyRestriction($qb)))
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
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();

        $this->loadDashboardCollections($rooms);

        return $rooms;
    }

    /**
     * Expression that matches every room the given user may access on the dashboard: either
     * as an invited participant (r.id IN rooms the user is invited to) or as a deputy of the
     * room's moderator (r.id IN rooms of moderators the user deputizes for).
     *
     * The IN (subquery) form is deliberately used instead of a correlated EXISTS: MySQL can
     * materialize the (small) set of the user's room ids once and then drive the outer query
     * through its primary key, keeping the main table access small regardless of the total
     * number of rooms. A correlated EXISTS would force the outer query to scan and sort a
     * large part of the rooms table just to find the first page.
     *
     * @param array $deputyRestrictions DQL fragments (referencing the alias r_d) restricting
     *                                  the rooms a deputy may see (e.g. creator != moderator)
     */
    private function memberOrDeputyIdCondition(QueryBuilder $qb, string $alias, array $deputyRestrictions = []): Orx
    {
        return $qb->expr()->orX(
            $qb->expr()->in($alias . '.id', $this->participantRoomIdsSubquery()->getDQL()),
            $qb->expr()->in($alias . '.id', $this->deputyRoomIdsSubquery($deputyRestrictions)->getDQL())
        );
    }

    /**
     * DQL subquery returning the ids of all rooms the user is invited to as a participant.
     */
    private function participantRoomIdsSubquery(): QueryBuilder
    {
        return $this->createQueryBuilder('r_p')
            ->select('r_p.id')
            ->join('r_p.user', 'u_p')
            ->where('u_p = :user');
    }

    /**
     * DQL subquery returning the ids of all rooms whose moderator the user deputizes for.
     *
     * @param array $extraConditions DQL fragments (referencing the alias r_d)
     */
    private function deputyRoomIdsSubquery(array $extraConditions = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('r_d')
            ->select('r_d.id')
            ->join('r_d.moderator', 'm_d')
            ->join('m_d.managerElement', 'me_d')
            ->join('me_d.deputy', 'd_d')
            ->where('d_d = :user');

        foreach ($extraConditions as $condition) {
            $qb->andWhere($condition);
        }

        return $qb;
    }

    /**
     * Restriction applied to deputy-visible rooms on the main dashboard (future + scheduled):
     * the room was not created by the moderator themselves, unless it is a regular meeting.
     */
    private function dashboardDeputyRestriction(QueryBuilder $qb): array
    {
        return [
            $qb->expr()->orX(
                'r_d.creator != r_d.moderator',
                $qb->expr()->andX(
                    $qb->expr()->orX($qb->expr()->isNull('r_d.persistantRoom'), 'r_d.persistantRoom = false'),
                    $qb->expr()->orX($qb->expr()->isNull('r_d.scheduleMeeting'), 'r_d.scheduleMeeting = false')
                )
            ),
        ];
    }

    private function resolveCursorRoom(?int $lastRoomId): ?Rooms
    {
        if ($lastRoomId === null || $lastRoomId <= 0) {
            return null;
        }

        return $this->find($lastRoomId);
    }

    /**
     * Applies the keyset (seek) condition for ascending pagination ordered by
     * (startUtc ASC, id ASC).
     */
    private function applyAscendingSeekCondition(QueryBuilder $qb, ?int $lastRoomId): void
    {
        $cursor = $this->resolveCursorRoom($lastRoomId);
        if (!$cursor) {
            return;
        }

        $qb->andWhere(
            $qb->expr()->orX(
                'r.startUtc > :lastStart',
                $qb->expr()->andX('r.startUtc = :lastStart', 'r.id > :lastId')
            )
        )
            ->setParameter('lastStart', $cursor->getStartUtc())
            ->setParameter('lastId', $cursor->getId());
    }

    /**
     * Applies the keyset (seek) condition for descending pagination ordered by
     * (startUtc DESC, id DESC).
     */
    private function applyDescendingSeekCondition(QueryBuilder $qb, ?Rooms $cursor): void
    {
        if (!$cursor) {
            return;
        }

        if ($cursor->getStartUtc() === null) {
            // Null-start rooms sort last in DESC order; continue through them by id.
            $qb->andWhere('r.startUtc IS NULL')
                ->andWhere('r.id < :lastId')
                ->setParameter('lastId', $cursor->getId());

            return;
        }

        $qb->andWhere(
            $qb->expr()->orX(
                'r.startUtc < :lastStart',
                $qb->expr()->andX('r.startUtc = :lastStart', 'r.id < :lastId')
            )
        )
            ->setParameter('lastStart', $cursor->getStartUtc())
            ->setParameter('lastId', $cursor->getId());
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

        // participants (r.user, ManyToMany) - ldapUserProperties is joined explicitly because
        // Doctrine does not apply eager joins to entities hydrated through a to-many fetch join.
        $em->createQueryBuilder()
            ->select('r', 'u', 'l')
            ->from(Rooms::class, 'r')
            ->leftJoin('r.user', 'u')
            ->leftJoin('u.ldapUserProperties', 'l')
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

        // transcriptions (OneToMany)
        $em->createQueryBuilder()
            ->select('r', 'tr')
            ->from(Rooms::class, 'r')
            ->leftJoin('r.transcriptions', 'tr')
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
