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
}
