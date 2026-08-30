<?php

namespace App\Tests\Dashboard;

use App\Entity\Rooms;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RoomsRepositoryDashboardTest extends KernelTestCase
{
    public function testFindRoomsForDashboardReturnsAllRoomTypes(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $rooms = $roomRepo->findRoomsForDashboard($user);

        $this->assertIsArray($rooms);
        $this->assertGreaterThan(0, count($rooms));

        $hasPersistent = false;
        $hasNonPersistent = false;
        foreach ($rooms as $room) {
            if ($room->getPersistantRoom()) {
                $hasPersistent = true;
            } else {
                $hasNonPersistent = true;
            }
        }
        $this->assertTrue($hasPersistent);
        $this->assertTrue($hasNonPersistent);
    }

    public function testFindRoomsForDashboardEagerLoadsRelationships(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $rooms = $roomRepo->findRoomsForDashboard($user);
        $this->assertNotEmpty($rooms);

        $room = $rooms[0];

        $this->assertNotNull($room->getServer());
        $this->assertNotNull($room->getModerator());
        $this->assertNotNull($room->getServer()->getServerName());
        $this->assertNotNull($room->getModerator()->getEmail());
    }

    public function testFindRoomsForDashboardExcludesPastRoomsWithoutStatus(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $rooms = $roomRepo->findRoomsForDashboard($user);

        $roomNames = array_map(fn($r) => $r->getName(), $rooms);
        $this->assertNotContains('Room Yesterday', $roomNames);
    }

    public function testFindRoomsForDashboardIncludesPersistentRooms(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $rooms = $roomRepo->findRoomsForDashboard($user);

        $persistentNames = [];
        foreach ($rooms as $room) {
            if ($room->getPersistantRoom()) {
                $persistentNames[] = $room->getName();
            }
        }
        $this->assertContains('This is a fixed room', $persistentNames);
    }

    public function testFindRoomsInPastReturnsOnlyPastRooms(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $rooms = $roomRepo->findRoomsInPast($user, 0);

        $this->assertIsArray($rooms);
        $now = new \DateTime('now', new \DateTimeZone('utc'));
        foreach ($rooms as $room) {
            $this->assertNotNull($room->getEndDateUtc());
            $this->assertLessThan($now->getTimestamp(), $room->getEndDateUtc()->getTimestamp());
        }
    }

    public function testFindRoomsInPastHasCorrectPagination(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $page0 = $roomRepo->findRoomsInPast($user, null);
        $pageSize = RoomsRepository::getPageSize();
        $this->assertLessThanOrEqual($pageSize + 1, count($page0));

        $sliced = array_slice($page0, 0, $pageSize);
        if (count($sliced) > 0) {
            $lastRoom = end($sliced);
            $page1 = $roomRepo->findRoomsInPast($user, $lastRoom->getId());
            $this->assertLessThanOrEqual($pageSize + 1, count($page1));

            $ids0 = array_map(fn($r) => $r->getId(), $sliced);
            $ids1 = array_map(fn($r) => $r->getId(), array_slice($page1, 0, $pageSize));
            $this->assertEmpty(array_intersect($ids0, $ids1));
        }
    }

    public function testFindRoomsInPastEagerLoadsRelationships(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $rooms = $roomRepo->findRoomsInPast($user, 0);

        foreach ($rooms as $room) {
            $this->assertNotNull($room->getServer());
            $this->assertNotNull($room->getServer()->getServerName());
            $this->assertNotNull($room->getModerator());
            $this->assertNotNull($room->getCreator());
        }
    }

    public function testFindFavoriteRoomsReturnsUserFavorites(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $user->addFavorite($room);
        $em = $this->getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        $em->persist($user);
        $em->flush();

        $favorites = $roomRepo->findFavoriteRooms($user);

        $favoriteNames = array_map(fn($r) => $r->getName(), $favorites);
        $this->assertContains('TestMeeting: 1', $favoriteNames);
    }

    public function testFindFavoriteRoomsEagerLoadsRelationships(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $user->addFavorite($room);
        $em = $this->getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        $em->persist($user);
        $em->flush();

        $favorites = $roomRepo->findFavoriteRooms($user);

        foreach ($favorites as $fav) {
            $this->assertNotNull($fav->getServer());
            $this->assertNotNull($fav->getServer()->getServerName());
            $this->assertNotNull($fav->getModerator());
            $this->assertNotNull($fav->getCreator());
        }
    }

    public function testFindRoomsForDashboardReturnsRoomsForCorrectUser(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);

        $user1 = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user4 = $userRepo->findOneBy(['email' => 'test@local4.de']);

        $roomsUser1 = $roomRepo->findRoomsForDashboard($user1);
        $roomsUser4 = $roomRepo->findRoomsForDashboard($user4);

        $this->assertGreaterThan(count($roomsUser4), count($roomsUser1));
    }

    public function testFindRoomsForDashboardIncludesDeputyRooms(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);

        $deputy = $userRepo->findOneBy(['email' => 'test@noTimeZone.de']);
        $this->assertNotNull($deputy);

        $rooms = $roomRepo->findRoomsForDashboard($deputy);

        $this->assertIsArray($rooms);
    }

    public function testPersistentRoomsAlwaysIncluded(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $rooms = $roomRepo->findRoomsForDashboard($user);

        $fixedRoomFound = false;
        $fixedRoomLobbyFound = false;
        $fixedRoomNoParticipantsFound = false;
        foreach ($rooms as $room) {
            if ($room->getName() === 'This is a fixed room') {
                $fixedRoomFound = true;
            }
            if ($room->getName() === 'This Room has no participants and fixed room') {
                $fixedRoomNoParticipantsFound = true;
            }
            if ($room->getName() === 'This Room has no participants and fixed room and Lobby activated') {
                $fixedRoomLobbyFound = true;
            }
        }
        $this->assertTrue($fixedRoomFound);
        $this->assertTrue($fixedRoomNoParticipantsFound);
        $this->assertTrue($fixedRoomLobbyFound);
    }

    public function testFindRoomsInPastDoesNotLazyLoadRepeater(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        [$rooms, $fetchQueries, $accessQueries] = $this->captureRepeaterQueries(
            fn () => $roomRepo->findRoomsInPast($user, 0)
        );

        $this->assertNotEmpty($rooms);
        $this->assertNotEmpty($fetchQueries, 'The SQL logger must capture the main query');
        $this->assertCount(0, $this->repeatLazyLoadQueries($fetchQueries), 'findRoomsInPast() must fetch-join repeater/repeaterProtoype');
        $this->assertCount(0, $this->repeatLazyLoadQueries($accessQueries), 'Accessing repeater/repeaterProtoype must not trigger lazy loads');
    }

    public function testFindFavoriteRoomsDoesNotLazyLoadRepeater(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $user->addFavorite($room);
        $em->persist($user);
        $em->flush();

        [$favorites, $fetchQueries, $accessQueries] = $this->captureRepeaterQueries(
            fn () => $roomRepo->findFavoriteRooms($user)
        );

        $this->assertNotEmpty($favorites);
        $this->assertNotEmpty($fetchQueries, 'The SQL logger must capture the main query');
        $this->assertCount(0, $this->repeatLazyLoadQueries($fetchQueries), 'findFavoriteRooms() must fetch-join repeater/repeaterProtoype');
        $this->assertCount(0, $this->repeatLazyLoadQueries($accessQueries), 'Accessing repeater/repeaterProtoype must not trigger lazy loads');
    }

    private function captureRepeaterQueries(callable $fetch): array
    {
        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $config = $em->getConnection()->getConfiguration();
        $previous = $config->getSQLLogger();

        $stack = new DebugStack();
        $config->setSQLLogger($stack);

        try {
            $rooms = $fetch();
            $fetchQueries = $stack->queries;

            $stack->queries = [];
            foreach ($rooms as $room) {
                $room->getRepeater();
                $room->getRepeaterProtoype();
            }
            $accessQueries = $stack->queries;
        } finally {
            $config->setSQLLogger($previous);
        }

        return [$rooms, $fetchQueries, $accessQueries];
    }

    private function repeatLazyLoadQueries(array $queries): array
    {
        return array_values(array_filter(
            $queries,
            static fn (array $query): bool => (bool) preg_match('/FROM\s+`repeat`\s+/', $query['sql'])
        ));
    }

    public function testFindRoomsForDashboardDoesNotLazyLoadTemplateCollections(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        [$rooms, $fetchQueries, $accessQueries] = $this->captureCollectionQueries(
            fn () => $roomRepo->findRoomsForDashboard($user)
        );

        $this->assertNotEmpty($rooms);
        $this->assertNotEmpty($fetchQueries, 'The SQL logger must capture the main query');
        $this->assertCount(0, $accessQueries, 'findRoomsForDashboard() must pre-load all template-accessed collections');
    }

    public function testFindRoomsInPastDoesNotLazyLoadTemplateCollections(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        [$rooms, $fetchQueries, $accessQueries] = $this->captureCollectionQueries(
            fn () => $roomRepo->findRoomsInPast($user, 0)
        );

        $this->assertNotEmpty($rooms);
        $this->assertNotEmpty($fetchQueries, 'The SQL logger must capture the main query');
        $this->assertCount(0, $accessQueries, 'findRoomsInPast() must pre-load all template-accessed collections');
    }

    public function testFindFavoriteRoomsDoesNotLazyLoadTemplateCollections(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $user->addFavorite($room);
        $em->persist($user);
        $em->flush();

        [$favorites, $fetchQueries, $accessQueries] = $this->captureCollectionQueries(
            fn () => $roomRepo->findFavoriteRooms($user)
        );

        $this->assertNotEmpty($favorites);
        $this->assertNotEmpty($fetchQueries, 'The SQL logger must capture the main query');
        $this->assertCount(0, $accessQueries, 'findFavoriteRooms() must pre-load all template-accessed collections');
    }

    public function testFindRoomsForDashboardMainQueryHasNoToManyFetchJoins(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        [$rooms, $fetchQueries] = $this->captureCollectionQueries(
            fn () => $roomRepo->findRoomsForDashboard($user)
        );

        $this->assertNotEmpty($rooms);
        $mainSql = $this->mainDashboardQuery($fetchQueries);
        $this->assertNotNull($mainSql, 'The main dashboard query must be captured by the SQL logger');

        // To-many associations must not be fetch-joined in the main query (that would cause a
        // cartesian product / row explosion). They are loaded via separate IN queries instead.
        $this->assertStringNotContainsString('LEFT JOIN rooms_user', $mainSql);
        $this->assertStringNotContainsString('LEFT JOIN scheduling', $mainSql);
        $this->assertStringNotContainsString('LEFT JOIN uploaded_recording', $mainSql);
        $this->assertStringNotContainsString('LEFT JOIN deputy', $mainSql);
    }

    /**
     * Fetches rooms while counting SQL queries, then accesses every to-many / inverse
     * association that the dashboard templates touch and counts the additional queries.
     *
     * @return array{0: Rooms[], 1: array, 2: array} [rooms, fetchQueries, accessQueries]
     */
    private function captureCollectionQueries(callable $fetch): array
    {
        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $config = $em->getConnection()->getConfiguration();
        $previous = $config->getSQLLogger();

        $stack = new DebugStack();
        $config->setSQLLogger($stack);

        try {
            $rooms = $fetch();
            $fetchQueries = $stack->queries;

            $stack->queries = [];
            foreach ($rooms as $room) {
                $room->getUser()->count();
                $room->getSchedulings()->count();
                $room->getUploadedRecordings()->count();
                $room->getTranscriptions()->count();
                $room->getRepeater();
                $room->getRepeaterProtoype();
                $room->getCallerRoom();
                foreach ($room->getUser() as $user) {
                    $user->getLdapUserProperties();
                }
                if ($room->getModerator()) {
                    $room->getModerator()->getDeputy()->toArray();
                    $room->getModerator()->getLdapUserProperties();
                }
                if ($room->getCreator()) {
                    $room->getCreator()->getLdapUserProperties();
                }
            }
            $accessQueries = $stack->queries;
        } finally {
            $config->setSQLLogger($previous);
        }

        return [$rooms, $fetchQueries, $accessQueries];
    }

    private function mainDashboardQuery(array $queries): ?string
    {
        foreach ($queries as $query) {
            if (str_contains($query['sql'], 'CASE WHEN r0_.start_utc IS NULL')) {
                return $query['sql'];
            }
        }

        return null;
    }

    public function testFindRoomsInFutureReturnsBoundedPage(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $rooms = $roomRepo->findRoomsInFuture($user, null);

        $this->assertNotEmpty($rooms);
        $this->assertLessThanOrEqual(RoomsRepository::getPageSize() + 1, count($rooms));
        $now = new \DateTime('now', new \DateTimeZone('utc'));
        foreach ($rooms as $room) {
            $this->assertNotNull($room->getEndDateUtc());
            $this->assertGreaterThan($now->getTimestamp(), $room->getEndDateUtc()->getTimestamp());
            $this->assertNotTrue($room->getPersistantRoom());
            $this->assertNotTrue($room->getScheduleMeeting());
        }
    }

    public function testFindRoomsInFutureKeysetPaginationHasNoOverlap(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $pageSize = RoomsRepository::getPageSize();
        $page0 = array_slice($roomRepo->findRoomsInFuture($user, null), 0, $pageSize);
        $this->assertCount($pageSize, $page0);

        $lastRoom = end($page0);
        $page1 = array_slice($roomRepo->findRoomsInFuture($user, $lastRoom->getId()), 0, $pageSize);
        $this->assertNotEmpty($page1);

        $ids0 = array_map(fn($r) => $r->getId(), $page0);
        $ids1 = array_map(fn($r) => $r->getId(), $page1);
        $this->assertEmpty(array_intersect($ids0, $ids1));
    }

    public function testFindScheduledRoomsReturnsBoundedPage(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $rooms = $roomRepo->findScheduledRooms($user, null);

        $this->assertNotEmpty($rooms);
        $this->assertLessThanOrEqual(RoomsRepository::getPageSize() + 1, count($rooms));
        foreach ($rooms as $room) {
            $this->assertTrue($room->getScheduleMeeting());
        }
    }

    public function testFindScheduledRoomsKeysetPaginationHasNoOverlap(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $pageSize = RoomsRepository::getPageSize();
        $page0 = array_slice($roomRepo->findScheduledRooms($user, null), 0, $pageSize);
        $this->assertCount($pageSize, $page0);

        $lastRoom = end($page0);
        $page1 = array_slice($roomRepo->findScheduledRooms($user, $lastRoom->getId()), 0, $pageSize);
        $this->assertNotEmpty($page1);

        $ids0 = array_map(fn($r) => $r->getId(), $page0);
        $ids1 = array_map(fn($r) => $r->getId(), $page1);
        $this->assertEmpty(array_intersect($ids0, $ids1));
    }

    public function testGetMyPersistantRoomsReturnsBoundedPageAndPreloadsCollections(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        [$rooms, $fetchQueries, $accessQueries] = $this->captureCollectionQueries(
            fn () => $roomRepo->getMyPersistantRooms($user, null)
        );

        $this->assertNotEmpty($rooms);
        $this->assertLessThanOrEqual(RoomsRepository::getPageSize() + 1, count($rooms));
        foreach ($rooms as $room) {
            $this->assertTrue($room->getPersistantRoom());
        }
        $this->assertNotEmpty($fetchQueries, 'The SQL logger must capture the main query');
        $this->assertCount(0, $accessQueries, 'getMyPersistantRooms() must pre-load all template-accessed collections');
    }

    public function testFindRoomsInFuturePreloadsTranscriptions(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        [$rooms, $fetchQueries, $accessQueries] = $this->captureCollectionQueries(
            fn () => $roomRepo->findRoomsInFuture($user, null)
        );

        $this->assertNotEmpty($rooms);
        $this->assertNotEmpty($fetchQueries, 'The SQL logger must capture the main query');
        $this->assertCount(0, $accessQueries, 'findRoomsInFuture() must pre-load all template-accessed collections');
    }
}
