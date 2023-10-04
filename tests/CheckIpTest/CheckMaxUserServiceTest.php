<?php

namespace App\Tests\CheckIpTest;

use App\Entity\Rooms;
use App\Entity\RoomStatus;
use App\Entity\RoomStatusParticipant;
use App\Service\CheckMaxUserService;
use App\Service\webhook\RoomStatusFrontendService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CheckMaxUserServiceTest extends KernelTestCase
{
    public function testMaxUserInConference(): void
    {
        $kernel = self::bootKernel();
        $room = new Rooms();
        $room->setMaxUser(2);

        $frontendService = $this->createMock(RoomStatusFrontendService::class);
        $frontendService
            ->method('numberOfOccupants')
            ->willReturn([
                new  RoomStatusParticipant(),
                new RoomStatusParticipant()
            ]);
        self::getContainer()->set(RoomStatusFrontendService::class,$frontendService);
        $checkMaxUserService = self::getContainer()->get(CheckMaxUserService::class);
        self::assertFalse($checkMaxUserService->isAllowedToEnter($room));
        $room->setMaxUser(3);
        self::assertTrue($checkMaxUserService->isAllowedToEnter($room));
        $room->setMaxUser(1);
        self::assertFalse($checkMaxUserService->isAllowedToEnter($room));
        $room->setMaxUser(null);
        self::assertTrue($checkMaxUserService->isAllowedToEnter($room));
    }

    public function testMaxUserInConferenceZeroInConference(): void
    {
        $kernel = self::bootKernel();
        $room = new Rooms();
        $room->setMaxUser(2);

        $frontendService = $this->createMock(RoomStatusFrontendService::class);
        $frontendService
            ->method('numberOfOccupants')
            ->willReturn([]);
        self::getContainer()->set(RoomStatusFrontendService::class,$frontendService);
        $checkMaxUserService = self::getContainer()->get(CheckMaxUserService::class);
        self::assertTrue($checkMaxUserService->isAllowedToEnter($room));
        $room->setMaxUser(3);
        self::assertTrue($checkMaxUserService->isAllowedToEnter($room));
        $room->setMaxUser(1);
        self::assertTrue($checkMaxUserService->isAllowedToEnter($room));
        $room->setMaxUser(null);
        self::assertTrue($checkMaxUserService->isAllowedToEnter($room));
    }


}
