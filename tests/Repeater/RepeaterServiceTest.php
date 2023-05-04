<?php

namespace App\Tests\Repeater;

use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Repository\RepeatRepository;
use App\Repository\RoomsRepository;
use App\Service\RepeaterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RepeaterServiceTest extends KernelTestCase
{
    public function testDailyRepeater(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
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
        self::assertEquals(new \DateTime('2021-01-15T15:00'), $repeat->getRooms()[0]->getStart());
        self::assertEquals(new \DateTime('2021-01-16T15:00'), $repeat->getRooms()[1]->getStart());
        self::assertEquals(new \DateTime('2021-01-17T15:00'), $repeat->getRooms()[2]->getStart());
        self::assertEquals(3, sizeof($repeat->getRooms()[0]->getUser()));
        self::assertEquals(3, sizeof($repeat->getRooms()[1]->getUser()));
        self::assertEquals(3, sizeof($repeat->getRooms()[2]->getUser()));
    }

    public function testWeeklyRepeater(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(1);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeaterWeeks(1);
        self::assertTrue($repeaterService->checkData($repeat));
        $repeaterService->createNewRepeater($repeat);
        self::assertEquals(3, sizeof($repeat->getRooms()));
        self::assertEquals(new \DateTime('2021-01-15T15:00'), $repeat->getRooms()[0]->getStart());
        self::assertEquals(new \DateTime('2021-01-22T15:00'), $repeat->getRooms()[1]->getStart());
        self::assertEquals(new \DateTime('2021-01-29T15:00'), $repeat->getRooms()[2]->getStart());
        self::assertEquals(3, sizeof($repeat->getRooms()[0]->getUser()));
        self::assertEquals(3, sizeof($repeat->getRooms()[1]->getUser()));
        self::assertEquals(3, sizeof($repeat->getRooms()[2]->getUser()));
    }

    public function testMonthlyRepeater(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(2);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatMontly(1);
        self::assertTrue($repeaterService->checkData($repeat));
        $repeaterService->createNewRepeater($repeat);
        self::assertEquals(3, sizeof($repeat->getRooms()));
        self::assertEquals(new \DateTime('2021-01-15T15:00'), $repeat->getRooms()[0]->getStart());
        self::assertEquals(new \DateTime('2021-02-15T15:00'), $repeat->getRooms()[1]->getStart());
        self::assertEquals(new \DateTime('2021-03-15T15:00'), $repeat->getRooms()[2]->getStart());
        self::assertEquals(3, sizeof($repeat->getRooms()[0]->getUser()));
        self::assertEquals(3, sizeof($repeat->getRooms()[1]->getUser()));
        self::assertEquals(3, sizeof($repeat->getRooms()[2]->getUser()));
    }

    public function testMonthlyRelativeRepeaterNextMonth(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(3);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatMonthlyRelativeHowOften(1);
        $repeat->setRepatMonthRelativWeekday(1);
        $repeat->setRepatMonthRelativNumber(0);
        self::assertTrue($repeaterService->checkData($repeat));
        $repeaterService->createNewRepeater($repeat);
        self::assertEquals(3, sizeof($repeat->getRooms()));
        self::assertEquals(new \DateTime('2021-02-01T15:00'), $repeat->getRooms()[0]->getStart());
        self::assertEquals(new \DateTime('2021-03-01T15:00'), $repeat->getRooms()[1]->getStart());
        self::assertEquals(new \DateTime('2021-04-05T15:00'), $repeat->getRooms()[2]->getStart());
        self::assertEquals(3, sizeof($repeat->getRooms()[0]->getUser()));
        self::assertEquals(3, sizeof($repeat->getRooms()[1]->getUser()));
        self::assertEquals(3, sizeof($repeat->getRooms()[2]->getUser()));
    }

    public function testMonthlyRelativeRepeaterThisMonth(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $room = $this->changeStart($room, '2021-01-01T15:00');
        $repeat = new Repeat();
        $repeat->setRepeatType(3);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatMonthlyRelativeHowOften(1);
        $repeat->setRepatMonthRelativWeekday(1);
        $repeat->setRepatMonthRelativNumber(0);
        self::assertTrue($repeaterService->checkData($repeat));
        $repeaterService->createNewRepeater($repeat);
        self::assertEquals(3, sizeof($repeat->getRooms()));
        self::assertEquals(3, sizeof($repeat->getRooms()));
        self::assertEquals(new \DateTime('2021-01-04T15:00'), $repeat->getRooms()[0]->getStart());
        self::assertEquals(new \DateTime('2021-02-01T15:00'), $repeat->getRooms()[1]->getStart());
        self::assertEquals(new \DateTime('2021-03-01T15:00'), $repeat->getRooms()[2]->getStart());
        self::assertEquals(3, sizeof($repeat->getRooms()[0]->getUser()));
        self::assertEquals(3, sizeof($repeat->getRooms()[1]->getUser()));
        self::assertEquals(3, sizeof($repeat->getRooms()[2]->getUser()));
    }

    public function testYearlyRepeater(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(4);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatYearly(1);
        self::assertTrue($repeaterService->checkData($repeat));
        $repeaterService->createNewRepeater($repeat);
        self::assertEquals(3, sizeof($repeat->getRooms()));
        self::assertEquals(new \DateTime('2021-01-15T15:00'), $repeat->getRooms()[0]->getStart());
        self::assertEquals(new \DateTime('2022-01-15T15:00'), $repeat->getRooms()[1]->getStart());
        self::assertEquals(new \DateTime('2023-01-15T15:00'), $repeat->getRooms()[2]->getStart());
        self::assertEquals(3, sizeof($repeat->getRooms()[0]->getUser()));
        self::assertEquals(3, sizeof($repeat->getRooms()[1]->getUser()));
        self::assertEquals(3, sizeof($repeat->getRooms()[2]->getUser()));
    }

    public function testYearlyRelativeRepeaterNextYear(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(5);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatYearlyRelativeHowOften(1);
        $repeat->setRepeatYearlyRelativeWeekday(1);
        $repeat->setRepeatYearlyRelativeNumber(0);
        $repeat->setRepeatYearlyRelativeMonth(0);
        self::assertTrue($repeaterService->checkData($repeat));
        $repeaterService->createNewRepeater($repeat);
        self::assertEquals(3, sizeof($repeat->getRooms()));
        self::assertEquals(new \DateTime('2022-01-03T15:00'), $repeat->getRooms()[0]->getStart());
        self::assertEquals(new \DateTime('2023-01-02T15:00'), $repeat->getRooms()[1]->getStart());
        self::assertEquals(new \DateTime('2024-01-01T15:00'), $repeat->getRooms()[2]->getStart());
        self::assertEquals(3, sizeof($repeat->getRooms()[0]->getUser()));
        self::assertEquals(3, sizeof($repeat->getRooms()[1]->getUser()));
        self::assertEquals(3, sizeof($repeat->getRooms()[2]->getUser()));
    }

    public function testYearlyRelativeRepeaterThisYear(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $room = $this->changeStart($room, '2021-01-01T15:00');
        $repeat = new Repeat();
        $repeat->setRepeatType(5);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatYearlyRelativeHowOften(1);
        $repeat->setRepeatYearlyRelativeWeekday(1);
        $repeat->setRepeatYearlyRelativeNumber(0);
        $repeat->setRepeatYearlyRelativeMonth(0);
        self::assertTrue($repeaterService->checkData($repeat));
        $repeaterService->createNewRepeater($repeat);
        self::assertEquals(3, sizeof($repeat->getRooms()));
        self::assertEquals(new \DateTime('2021-01-04T15:00'), $repeat->getRooms()[0]->getStart());
        self::assertEquals(new \DateTime('2022-01-03T15:00'), $repeat->getRooms()[1]->getStart());
        self::assertEquals(new \DateTime('2023-01-02T15:00'), $repeat->getRooms()[2]->getStart());
        self::assertEquals(3, sizeof($repeat->getRooms()[0]->getUser()));
        self::assertEquals(3, sizeof($repeat->getRooms()[1]->getUser()));
        self::assertEquals(3, sizeof($repeat->getRooms()[2]->getUser()));
    }


    public function testRepeatSendEmail(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(0);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeaterDays(1);
        $repeaterService->createNewRepeater($repeat);
        self::assertEquals(3, sizeof($repeat->getRooms()));
        self::assertEquals(new \DateTime('2021-01-15T15:00'), $repeat->getRooms()[0]->getStart());
        self::assertEquals(new \DateTime('2021-01-16T15:00'), $repeat->getRooms()[1]->getStart());
        self::assertEquals(new \DateTime('2021-01-17T15:00'), $repeat->getRooms()[2]->getStart());
        self::assertEquals(3, sizeof($repeat->getRooms()[0]->getUser()));
        self::assertEquals(3, sizeof($repeat->getRooms()[1]->getUser()));
        self::assertEquals(3, sizeof($repeat->getRooms()[2]->getUser()));
        $repeaterService->sendEMail($repeat, 'email/repeaterNew.html.twig', 'Eine neue Serienvideokonferenz wurde erstellt', ['room' => $repeat->getPrototyp()]);
        $repeaterService->sendEMail($repeat, 'email/repeaterNew.html.twig', 'Eine neue Serienvideokonferenz wurde erstellt', ['room' => $repeat->getPrototyp()], 'REQUEST', $repeat->getPrototyp()->getUser());
    }
    public function testChangeRepeaterRooms(): void
    {
        $kernel = self::bootKernel();
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        foreach ($room->getUser() as $data) {
            $room->addPrototypeUser($data);
            $room->removeUser($data);
        }
        $manager->persist($room);
        $manager->flush();

        $repeat = new Repeat();
        $repeat->setRepeatType(0);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeaterDays(1);
        $repeat = $repeaterService->createNewRepeater($repeat);
        $manager->persist($repeat);
        $manager->flush();
        $repeaterService->addUserRepeat($repeat);
        self::assertEquals(3, sizeof($repeat->getRooms()));
        self::assertEquals(new \DateTime('2021-01-15T15:00'), $repeat->getRooms()[0]->getStart());
        self::assertEquals(new \DateTime('2021-01-16T15:00'), $repeat->getRooms()[1]->getStart());
        self::assertEquals(new \DateTime('2021-01-17T15:00'), $repeat->getRooms()[2]->getStart());
        self::assertEquals(3, sizeof($repeat->getRooms()[0]->getUser()));
        self::assertEquals(3, sizeof($repeat->getRooms()[1]->getUser()));
        self::assertEquals(3, sizeof($repeat->getRooms()[2]->getUser()));
        self::assertEquals(3, sizeof($repeat->getPrototyp()->getPrototypeUsers()));
        $manager->persist($repeat);
        $manager->flush();
        $roomTmp = $roomRepo->find($repeat->getRooms()[2]->getId());
        $repTmp = $roomTmp->getRepeater();
        $roomNew = $repTmp->getPrototyp();
        $roomNew->setRepeaterProtoype($repTmp);
        $roomNew->setDuration(90);
        $roomNew->setStart(new \DateTime('2021-01-20T18:00'));
        $rep = $repeaterService->prepareRepeater($roomNew);
        self::assertEquals('2021-01-20T18:00', $rep->getStartDate()->format('Y-m-d') . 'T' . $rep->getStartDate()->format('H:i'));
        $rep = $repeaterService->replaceRooms($roomNew);
        $repRepo = self::getContainer()->get(RepeatRepository::class);
        $repTmp = $repRepo->find($repTmp->getId());
        self::assertEquals('Sie haben erfolgreich einen Serientermin bearbeitet.', $rep);
        self::assertEquals(3, sizeof($repTmp->getRooms()));

        $date = new \DateTime('2021-01-20T18:00');
        foreach ($repTmp->getRooms() as $data) {
            self::assertEquals($date, $data->getStart());
            $date->modify('+1day');
            self::assertEquals(3, sizeof($data->getUser()));
            self::assertEquals(3, sizeof($data->getCallerIds()));
            self::assertNotNull($data->getCallerRoom());
        }
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

    public function testcreateCallerId(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $room = $this->changeStart($room, '2021-01-01T15:00');
        $repeat = new Repeat();
        $repeat->setRepeatType(5);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatYearlyRelativeHowOften(1);
        $repeat->setRepeatYearlyRelativeWeekday(1);
        $repeat->setRepeatYearlyRelativeNumber(0);
        $repeat->setRepeatYearlyRelativeMonth(0);
        self::assertTrue($repeaterService->checkData($repeat));
        $repeaterService->createNewRepeater($repeat);
        $repeaterService->createNewCaller($repeat);
        foreach ($repeat->getRooms() as $data) {
            self::assertEquals(3, sizeof($data->getCallerIds()));
            self::assertNotNull($data->getCallerRoom());
        }
    }
}
