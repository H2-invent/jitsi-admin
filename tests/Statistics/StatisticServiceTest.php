<?php

namespace App\Tests\Statistics;

use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Repository\RoomsRepository;
use App\Repository\ServerRepository;
use App\Service\AdminService;
use App\Service\RepeaterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StatisticServiceTest extends KernelTestCase
{
    public function testStatiskidAdmin(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $adminService = self::getContainer()->get(AdminService::class);
        $serverRepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverRepo->findOneBy(['url' => 'meet.jit.si2']);
        $chart = $adminService->createChart($server);
        self::assertEquals(61, sizeof($chart));
        $rooms = 0;
        $part = 0;
        $part_real = 0;

        foreach ($chart as $c) {
            $rooms = $rooms + $c['rooms'];
            $part = $part + $c['participants'];
            $part_real = $part_real + $c['participants_real'];
        }
        self::assertEquals(47, $rooms);
        self::assertEquals(131, $part);
        self::assertEquals(4, $part_real);
    }

    public function testStatiskidAdminwithRepeater(): void
    {
        $kernel = self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);

        $repeat = new Repeat();
        $repeat->setRepeatType(0);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeaterDays(1);
        self::assertTrue($repeaterService->checkData($repeat));
        $repeaterService->createNewRepeater($repeat);
        self::assertEquals(3, sizeof($repeat->getRooms()));
        $this->assertSame('test', $kernel->getEnvironment());
        $adminService = self::getContainer()->get(AdminService::class);
        $serverRepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverRepo->findOneBy(['url' => 'meet.jit.si']);
        $chart = $adminService->createChart($server);
    }

    private function prepareRoom(RoomsRepository $roomsRepository)
    {
        $manager = self::getContainer()->get(EntityManagerInterface::class);

        $rooms = $roomsRepository->findOneBy(['name' => 'TestMeeting: 0']);
        $rooms = $this->changeStart($rooms, '2021-01-15T15:00');
        foreach ($rooms->getUser() as $data) {
            if ($data !== $rooms->getModerator()) {
                $userAttr = new RoomsUser();
                $userAttr->setRoom($rooms);
                $userAttr->setUser($data);
                $userAttr->setModerator(true);
                $userAttr->setShareDisplay(true);
                $manager->persist($userAttr);
            }
        }
        $manager->flush();
        return $rooms;
    }

    private function changeStart(Rooms $rooms, $startDate)
    {
        $rooms->setStart(new \DateTime($startDate));
        $endDate = clone $rooms->getStart();
        $endDate->modify('+' . $rooms->getDuration() . 'min');
        $rooms->setEnddate($endDate);
        return $rooms;
    }
}
