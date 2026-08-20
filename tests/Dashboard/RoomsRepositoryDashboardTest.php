<?php

namespace App\Tests\Dashboard;

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

        $page0 = $roomRepo->findRoomsInPast($user, 0);
        $page1 = $roomRepo->findRoomsInPast($user, 1);

        $this->assertLessThanOrEqual(8, count($page0));

        if (count($page0) > 0 && count($page1) > 0) {
            $ids0 = array_map(fn($r) => $r->getId(), $page0);
            $ids1 = array_map(fn($r) => $r->getId(), $page1);
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
}
