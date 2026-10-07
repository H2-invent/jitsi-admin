<?php

namespace App\Tests\Dashboard;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\DashboardService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DashboardServiceTest extends KernelTestCase
{
    public function testCategorizeRoomsPersistent(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $dashboardService = $this->getContainer()->get(DashboardService::class);

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $rooms = $roomRepo->findRoomsForDashboard($user);

        $result = $dashboardService->categorizeRooms($rooms, $user);

        $this->assertArrayHasKey('roomsFuture', $result);
        $this->assertArrayHasKey('roomsNow', $result);
        $this->assertArrayHasKey('roomsToday', $result);
        $this->assertArrayHasKey('persistantRooms', $result);
        $this->assertArrayHasKey('scheduledRooms', $result);
        $this->assertArrayHasKey('roomIds', $result);

        $this->assertGreaterThanOrEqual(3, count($result['persistantRooms']));
        foreach ($result['persistantRooms'] as $room) {
            $this->assertTrue($room->getPersistantRoom());
        }
    }

    public function testCategorizeRoomsScheduled(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $dashboardService = $this->getContainer()->get(DashboardService::class);

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $scheduledRooms = $roomRepo->getMyScheduledRooms($user);

        $result = $dashboardService->categorizeRooms($scheduledRooms, $user);

        $this->assertGreaterThanOrEqual(1, count($result['scheduledRooms']));
        foreach ($result['scheduledRooms'] as $room) {
            $this->assertTrue($room->getScheduleMeeting());
        }
    }

    public function testCategorizeRoomsFutureAndRunning(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $dashboardService = $this->getContainer()->get(DashboardService::class);

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $rooms = $roomRepo->findRoomsForDashboard($user);

        $result = $dashboardService->categorizeRooms($rooms, $user);

        $this->assertGreaterThan(0, count($result['roomsFuture']));
        $this->assertArrayHasKey('scheduledRooms', $result);

        $scheduledRoomNames = [];
        foreach ($result['scheduledRooms'] as $room) {
            $scheduledRoomNames[] = $room->getName();
        }
        foreach ($result['roomsFuture'] as $dateGroup) {
            foreach ($dateGroup as $room) {
                $this->assertNotTrue($room->getPersistantRoom());
                $this->assertNotContains($room->getName(), $scheduledRoomNames);
            }
        }
    }

    public function testCategorizeRoomsReturnsAllRoomIds(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $dashboardService = $this->getContainer()->get(DashboardService::class);

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $rooms = $roomRepo->findRoomsForDashboard($user);

        $result = $dashboardService->categorizeRooms($rooms, $user);

        $this->assertCount(count($rooms), $result['roomIds']);

        foreach ($rooms as $room) {
            $this->assertContains($room->getId(), $result['roomIds']);
        }
    }

    public function testCategorizeRoomsRunningRoom(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $dashboardService = $this->getContainer()->get(DashboardService::class);

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $runningRoom = $roomRepo->findOneBy(['name' => 'Running Room']);
        $this->assertNotNull($runningRoom);

        $result = $dashboardService->categorizeRooms([$runningRoom], $user);

        $this->assertGreaterThanOrEqual(1, count($result['roomsNow']));
        $runningNames = array_map(fn($r) => $r->getName(), $result['roomsNow']);
        $this->assertContains('Running Room', $runningNames);
    }

    public function testCategorizeRoomsEmptyInputReturnsEmptyArrays(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $userRepo = $this->getContainer()->get(UserRepository::class);
        $dashboardService = $this->getContainer()->get(DashboardService::class);

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $result = $dashboardService->categorizeRooms([], $user);

        $this->assertEmpty($result['roomsFuture']);
        $this->assertEmpty($result['roomsNow']);
        $this->assertEmpty($result['roomsToday']);
        $this->assertEmpty($result['persistantRooms']);
        $this->assertEmpty($result['scheduledRooms']);
        $this->assertEmpty($result['roomIds']);
    }

    public function testCategorizeRoomsTodayRoom(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $dashboardService = $this->getContainer()->get(DashboardService::class);

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $this->assertNotNull($room);

        $result = $dashboardService->categorizeRooms([$room], $user);

        $todayNames = array_map(fn($r) => $r->getName(), $result['roomsToday']);
        $this->assertContains('TestMeeting: 0', $todayNames);
    }

    public function testGetRoomClosedForStartMapReturnsEmptyForModeratorRoom(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $dashboardService = $this->getContainer()->get(DashboardService::class);

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $room = $roomRepo->findOneBy(['name' => 'Room Tomorrow']);
        $this->assertNotNull($room);

        $result = $dashboardService->getRoomClosedForStartMap([$room], $user, []);

        // Moderator is allowed to enter, so no "closed" message
        $this->assertArrayNotHasKey($room->getId(), $result);
    }

    public function testGetRoomClosedForStartMapReturnsClosedForPastRoom(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $dashboardService = $this->getContainer()->get(DashboardService::class);

        $nonModerator = $userRepo->findOneBy(['email' => 'test@local4.de']);
        $room = $roomRepo->findOneBy(['name' => 'Room Yesterday']);
        $this->assertNotNull($room);

        $result = $dashboardService->getRoomClosedForStartMap([$room], $nonModerator, []);

        $this->assertArrayHasKey($room->getId(), $result);
        $this->assertStringContainsString('Der Beitritt ist nur von', $result[$room->getId()]);
    }

    public function testGetRoomClosedForStartMapSkipsPersistentRooms(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $dashboardService = $this->getContainer()->get(DashboardService::class);

        $nonModerator = $userRepo->findOneBy(['email' => 'test@local4.de']);
        $room = $roomRepo->findOneBy(['name' => 'This is a fixed room']);
        $this->assertNotNull($room);

        $result = $dashboardService->getRoomClosedForStartMap([$room], $nonModerator, []);

        $this->assertArrayNotHasKey($room->getId(), $result, 'Persistent rooms must never appear in closed-for-start map');
    }

    public function testGetRoomClosedForStartMapSkipsActiveStatusRooms(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $dashboardService = $this->getContainer()->get(DashboardService::class);

        $nonModerator = $userRepo->findOneBy(['email' => 'test@local4.de']);
        $room = $roomRepo->findOneBy(['name' => 'Running Room']);
        $this->assertNotNull($room);

        $roomStatusOpenMap = [$room->getId() => true];

        $result = $dashboardService->getRoomClosedForStartMap([$room], $nonModerator, $roomStatusOpenMap);

        $this->assertArrayNotHasKey($room->getId(), $result, 'Rooms with an active status must never be closed for start');
    }

    public function testGetRoomClosedForStartMapEmptyForEmptyInput(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $userRepo = $this->getContainer()->get(UserRepository::class);
        $dashboardService = $this->getContainer()->get(DashboardService::class);

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $result = $dashboardService->getRoomClosedForStartMap([], $user, []);

        $this->assertEmpty($result);
    }

    public function testGetRoomClosedForStartMapSkipsRoomWithNoDates(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $dashboardService = $this->getContainer()->get(DashboardService::class);

        $nonModerator = $userRepo->findOneBy(['email' => 'test@local4.de']);
        $room = $roomRepo->findOneBy(['name' => 'This is a fixed room']);
        $this->assertNotNull($room);

        $result = $dashboardService->getRoomClosedForStartMap([$room], $nonModerator, []);

        $this->assertArrayNotHasKey($room->getId(), $result);
    }
}
