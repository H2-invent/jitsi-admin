<?php

namespace App\Tests\Schedule;

use App\Entity\Rooms;
use App\Entity\Scheduling;
use App\Entity\SchedulingTime;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\SchedulingService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SchedulingCheckEmailTest extends KernelTestCase
{
    public function testOneUSer(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $schedulingService = self::getContainer()->get(SchedulingService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user1 = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $room = $roomRepo->findOneBy(['name' => 'Termin finden: 0']);

        self::assertFalse($schedulingService->checkIfUserVoted($room->getSchedulings()[0], $user1));
        $schedulingService->voteForSchedulingTime($user1, $room->getSchedulings()[0]->getSchedulingTimes()[0], 0);
        self::assertTrue($schedulingService->checkIfUserVoted($room->getSchedulings()[0], $user1));
        for ($i = 0; $i < 5; $i++) {
            $schedulingService->voteForSchedulingTime($user1, $room->getSchedulings()[0]->getSchedulingTimes()[$i], 0);
        }
        self::assertTrue($schedulingService->checkIfUserVoted($room->getSchedulings()[0], $user1));
    }
    public function testAllUSer(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $schedulingService = self::getContainer()->get(SchedulingService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user1 = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $user3 = $userRepo->findOneBy(['email' => 'test@local3.de']);
        $room = $roomRepo->findOneBy(['name' => 'Termin finden: 0']);

        self::assertFalse($schedulingService->checkIfAllUserVoted($room->getSchedulings()[0]));
        $schedulingService->voteForSchedulingTimeOnly($user1, $room->getSchedulings()[0]->getSchedulingTimes()[0], 0);
        self::assertFalse($schedulingService->checkIfAllUserVoted($room->getSchedulings()[0]));
        $schedulingService->voteForSchedulingTimeOnly($user2, $room->getSchedulings()[0]->getSchedulingTimes()[0], 0);
        self::assertFalse($schedulingService->checkIfAllUserVoted($room->getSchedulings()[0]));
        $schedulingService->voteForSchedulingTimeOnly($user3, $room->getSchedulings()[0]->getSchedulingTimes()[0], 0);
        self::assertTrue($schedulingService->checkIfAllUserVoted($room->getSchedulings()[0]));
        self::assertFalse($schedulingService->checkIfAllUserVoted($room->getSchedulings()[0]));
    }

    public function testSendEmail(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $schedulingService = self::getContainer()->get(SchedulingService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user1 = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $user3 = $userRepo->findOneBy(['email' => 'test@local3.de']);
        $room = $roomRepo->findOneBy(['name' => 'Termin finden: 0']);

        $schedulingService->sendEmailWhenAllFinish($room);
        self::assertEmailCount(0);
        $schedulingService->voteForSchedulingTime($user1, $room->getSchedulings()[0]->getSchedulingTimes()[0], 0);
        $schedulingService->sendEmailWhenAllFinish($room);
        self::assertEmailCount(0);
        $schedulingService->voteForSchedulingTime($user2, $room->getSchedulings()[0]->getSchedulingTimes()[0], 0);
        $schedulingService->sendEmailWhenAllFinish($room);
        self::assertEmailCount(0);
        $schedulingService->voteForSchedulingTime($user3, $room->getSchedulings()[0]->getSchedulingTimes()[0], 0);
        $schedulingService->sendEmailWhenAllFinish($room);
        self::assertEmailCount(1);
        $schedulingService->sendEmailWhenAllFinish($room);
        self::assertEmailCount(1);

    }

}
