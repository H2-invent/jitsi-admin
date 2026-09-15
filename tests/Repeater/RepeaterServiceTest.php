<?php

namespace App\Tests\Repeater;

use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Repository\RepeatRepository;
use App\Repository\RoomsRepository;
use App\Service\IcsService;
use App\Service\Jigasi\JigasiService;
use App\Service\JoinUrlGeneratorService;
use App\Service\MailerService;
use App\Service\RepeaterService;
use App\Service\caller\CallerPrepareService;
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
        self::assertEquals(new \DateTimeImmutable('2021-01-15T15:00'), $repeat->getRooms()[0]->getStart());
        self::assertEquals(new \DateTimeImmutable('2021-01-16T15:00'), $repeat->getRooms()[1]->getStart());
        self::assertEquals(new \DateTimeImmutable('2021-01-17T15:00'), $repeat->getRooms()[2]->getStart());
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
        self::assertEquals(new \DateTimeImmutable('2021-01-15T15:00'), $repeat->getRooms()[0]->getStart());
        self::assertEquals(new \DateTimeImmutable('2021-01-22T15:00'), $repeat->getRooms()[1]->getStart());
        self::assertEquals(new \DateTimeImmutable('2021-01-29T15:00'), $repeat->getRooms()[2]->getStart());
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
        self::assertEquals(new \DateTimeImmutable('2021-01-15T15:00'), $repeat->getRooms()[0]->getStart());
        self::assertEquals(new \DateTimeImmutable('2021-02-15T15:00'), $repeat->getRooms()[1]->getStart());
        self::assertEquals(new \DateTimeImmutable('2021-03-15T15:00'), $repeat->getRooms()[2]->getStart());
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
        self::assertEquals(new \DateTimeImmutable('2021-02-01T15:00'), $repeat->getRooms()[0]->getStart());
        self::assertEquals(new \DateTimeImmutable('2021-03-01T15:00'), $repeat->getRooms()[1]->getStart());
        self::assertEquals(new \DateTimeImmutable('2021-04-05T15:00'), $repeat->getRooms()[2]->getStart());
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
        self::assertEquals(new \DateTimeImmutable('2021-01-04T15:00'), $repeat->getRooms()[0]->getStart());
        self::assertEquals(new \DateTimeImmutable('2021-02-01T15:00'), $repeat->getRooms()[1]->getStart());
        self::assertEquals(new \DateTimeImmutable('2021-03-01T15:00'), $repeat->getRooms()[2]->getStart());
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
        self::assertEquals(new \DateTimeImmutable('2021-01-15T15:00'), $repeat->getRooms()[0]->getStart());
        self::assertEquals(new \DateTimeImmutable('2022-01-15T15:00'), $repeat->getRooms()[1]->getStart());
        self::assertEquals(new \DateTimeImmutable('2023-01-15T15:00'), $repeat->getRooms()[2]->getStart());
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
        self::assertEquals(new \DateTimeImmutable('2022-01-03T15:00'), $repeat->getRooms()[0]->getStart());
        self::assertEquals(new \DateTimeImmutable('2023-01-02T15:00'), $repeat->getRooms()[1]->getStart());
        self::assertEquals(new \DateTimeImmutable('2024-01-01T15:00'), $repeat->getRooms()[2]->getStart());
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
        self::assertEquals(new \DateTimeImmutable('2021-01-04T15:00'), $repeat->getRooms()[0]->getStart());
        self::assertEquals(new \DateTimeImmutable('2022-01-03T15:00'), $repeat->getRooms()[1]->getStart());
        self::assertEquals(new \DateTimeImmutable('2023-01-02T15:00'), $repeat->getRooms()[2]->getStart());
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
        self::assertEquals(new \DateTimeImmutable('2021-01-15T15:00'), $repeat->getRooms()[0]->getStart());
        self::assertEquals(new \DateTimeImmutable('2021-01-16T15:00'), $repeat->getRooms()[1]->getStart());
        self::assertEquals(new \DateTimeImmutable('2021-01-17T15:00'), $repeat->getRooms()[2]->getStart());
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
        self::assertEquals(new \DateTimeImmutable('2021-01-15T15:00'), $repeat->getRooms()[0]->getStart());
        self::assertEquals(new \DateTimeImmutable('2021-01-16T15:00'), $repeat->getRooms()[1]->getStart());
        self::assertEquals(new \DateTimeImmutable('2021-01-17T15:00'), $repeat->getRooms()[2]->getStart());
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
        $roomNew->setStart(new \DateTimeImmutable('2021-01-20T18:00'));
        $rep = $repeaterService->prepareRepeater($roomNew);
        self::assertEquals('2021-01-20T18:00', $rep->getStartDate()->format('Y-m-d') . 'T' . $rep->getStartDate()->format('H:i'));
        $rep = $repeaterService->replaceRooms($roomNew);
        $repRepo = self::getContainer()->get(RepeatRepository::class);
        $repTmp = $repRepo->find($repTmp->getId());
        self::assertEquals('Sie haben erfolgreich einen Serientermin bearbeitet.', $rep);
        self::assertEquals(3, sizeof($repTmp->getRooms()));

        $date = new \DateTimeImmutable('2021-01-20T18:00');
        foreach ($repTmp->getRooms() as $data) {
            self::assertEquals($date, $data->getStart());
            $date = $date->modify('+1day');
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

    private function changeStart(Rooms $rooms, $startDate, ?string $timeZone = null)
    {
        $rooms->setStart(new \DateTimeImmutable($startDate, $timeZone ? new \DateTimeZone($timeZone) : null));
        $endDate = clone $rooms->getStart();
        $endDate = $endDate->modify('+' . $rooms->getDuration() . 'min');
        $rooms->setEnddate($endDate);
        return $rooms;
    }

    private function assertRoomStartDates(Repeat $repeat, array $expected): void
    {
        self::assertCount(count($expected), $repeat->getRooms());
        $i = 0;
        foreach ($repeat->getRooms() as $room) {
            self::assertSame(
                $expected[$i],
                $room->getStart()->format('Y-m-d H:i'),
                'Unexpected start date for occurrence ' . $i
            );
            $i++;
        }
    }

    private function repeaterServiceWithMailer(MailerService $mailer): RepeaterService
    {
        $container = self::getContainer();
        return new RepeaterService(
            $container->get(CallerPrepareService::class),
            $container->get('twig'),
            $container->get('translator'),
            $mailer,
            $container->get(EntityManagerInterface::class),
            $container->get(JoinUrlGeneratorService::class),
            $container->get(JigasiService::class)
        );
    }

    private function regenerateSeries(RepeaterService $repeaterService, EntityManagerInterface $manager, Repeat $repeat, string $start): Repeat
    {
        $manager->clear();
        $repeat = $manager->getRepository(Repeat::class)->find($repeat->getId());
        $prototype = $repeat->getPrototyp();
        $prototype->setRepeaterProtoype($repeat);
        $prototype->setDuration(60);
        $prototype->setStart(new \DateTimeImmutable($start));
        $repeaterService->replaceRooms($prototype);
        return $repeat;
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

    public function testMonthlyRepeaterEndOfMonthOverflowsToNextMonth(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $room = $this->changeStart($room, '2021-01-31T15:00');
        $repeat = new Repeat();
        $repeat->setRepeatType(2);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatMontly(1);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-01-31 15:00', '2021-03-03 15:00', '2021-04-03 15:00']);
    }

    public function testDailyRepeaterWithMultiDayInterval(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(0);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeaterDays(3);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-01-15 15:00', '2021-01-18 15:00', '2021-01-21 15:00']);
    }

    public function testWeeklyRepeaterWithMultiWeekInterval(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(1);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeaterWeeks(2);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-01-15 15:00', '2021-01-29 15:00', '2021-02-12 15:00']);
    }

    public function testMonthlyRepeaterWithMultiMonthInterval(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(2);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatMontly(3);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-01-15 15:00', '2021-04-15 15:00', '2021-07-15 15:00']);
    }

    public function testYearlyRepeaterWithMultiYearInterval(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(4);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatYearly(2);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-01-15 15:00', '2023-01-15 15:00', '2025-01-15 15:00']);
    }

    public function testMonthlyRelativeRepeaterWithMultiMonthInterval(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(3);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(4);
        $repeat->setRepeatMonthlyRelativeHowOften(2);
        $repeat->setRepatMonthRelativWeekday(1);
        $repeat->setRepatMonthRelativNumber(0);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-02-01 15:00', '2021-04-05 15:00', '2021-06-07 15:00', '2021-08-02 15:00']);
    }

    public function testYearlyRelativeRepeaterWithMultiYearInterval(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(5);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatYearlyRelativeHowOften(2);
        $repeat->setRepeatYearlyRelativeWeekday(1);
        $repeat->setRepeatYearlyRelativeNumber(0);
        $repeat->setRepeatYearlyRelativeMonth(0);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2022-01-03 15:00', '2024-01-01 15:00', '2026-01-05 15:00']);
    }

    public function testMonthlyRelativeRepeaterLastWeekday(): void
    {
        self::bootKernel();
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
        $repeat->setRepatMonthRelativNumber(5);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-01-25 15:00', '2021-02-22 15:00', '2021-03-29 15:00']);
    }

    public function testMonthlyRelativeRepeaterFifthWeekdayOverflowsWhenAbsent(): void
    {
        self::bootKernel();
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
        $repeat->setRepatMonthRelativNumber(4);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-02-01 15:00', '2021-03-01 15:00', '2021-05-03 15:00']);
    }

    public function testYearlyRelativeRepeaterLastWeekday(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(5);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatYearlyRelativeHowOften(1);
        $repeat->setRepeatYearlyRelativeWeekday(1);
        $repeat->setRepeatYearlyRelativeNumber(5);
        $repeat->setRepeatYearlyRelativeMonth(0);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-01-25 15:00', '2022-01-31 15:00', '2023-01-30 15:00']);
    }

    public function testYearlyRelativeRepeaterFifthWeekdayOverflowsWhenAbsent(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(5);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatYearlyRelativeHowOften(1);
        $repeat->setRepeatYearlyRelativeWeekday(1);
        $repeat->setRepeatYearlyRelativeNumber(4);
        $repeat->setRepeatYearlyRelativeMonth(0);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-02-01 15:00', '2022-01-31 15:00', '2023-01-30 15:00']);
    }

    public function testYearlyRelativeRepeaterWithJulyMonth(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(5);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatYearlyRelativeHowOften(1);
        $repeat->setRepeatYearlyRelativeWeekday(1);
        $repeat->setRepeatYearlyRelativeNumber(0);
        $repeat->setRepeatYearlyRelativeMonth(6);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-07-05 15:00', '2022-07-04 15:00', '2023-07-03 15:00']);
    }

    public function testYearlyRelativeRepeaterWithDecemberMonth(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(5);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatYearlyRelativeHowOften(1);
        $repeat->setRepeatYearlyRelativeWeekday(1);
        $repeat->setRepeatYearlyRelativeNumber(0);
        $repeat->setRepeatYearlyRelativeMonth(11);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-12-06 15:00', '2022-12-05 15:00', '2023-12-04 15:00']);
    }

    public function testDailyRepeaterCrossesYearBoundary(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $room = $this->changeStart($room, '2021-12-30T15:00');
        $repeat = new Repeat();
        $repeat->setRepeatType(0);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(4);
        $repeat->setRepeaterDays(1);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-12-30 15:00', '2021-12-31 15:00', '2022-01-01 15:00', '2022-01-02 15:00']);
    }

    public function testWeeklyRepeaterCrossesYearBoundary(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $room = $this->changeStart($room, '2021-12-20T15:00');
        $repeat = new Repeat();
        $repeat->setRepeatType(1);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeaterWeeks(1);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-12-20 15:00', '2021-12-27 15:00', '2022-01-03 15:00']);
    }

    public function testMonthlyRepeaterCrossesYearBoundary(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $room = $this->changeStart($room, '2021-11-15T15:00');
        $repeat = new Repeat();
        $repeat->setRepeatType(2);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatMontly(1);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-11-15 15:00', '2021-12-15 15:00', '2022-01-15 15:00']);
    }

    public function testGeneratedRoomsEndDateMatchesStartPlusDuration(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(1);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeaterWeeks(1);
        $repeaterService->createNewRepeater($repeat);

        foreach ($repeat->getRooms() as $generatedRoom) {
            self::assertSame(
                $generatedRoom->getStart()->modify('+60 min')->format('Y-m-d H:i'),
                $generatedRoom->getEnddate()->format('Y-m-d H:i')
            );
        }
    }

    public function testDailyRepeaterPreservesWallClockAcrossDstSpringForward(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $room = $this->changeStart($room, '2021-03-27T15:00', 'Europe/Berlin');
        $repeat = new Repeat();
        $repeat->setRepeatType(0);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeaterDays(1);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-03-27 15:00', '2021-03-28 15:00', '2021-03-29 15:00']);
    }

    public function testDailyRepeaterPreservesWallClockAcrossDstAutumnBack(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $room = $this->changeStart($room, '2021-10-30T15:00', 'Europe/Berlin');
        $repeat = new Repeat();
        $repeat->setRepeatType(0);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeaterDays(1);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-10-30 15:00', '2021-10-31 15:00', '2021-11-01 15:00']);
    }

    public function testEachRepeatTypeGeneratesExactlyRepetationRooms(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(0);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(4);
        $repeat->setRepeaterDays(1);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(4, $repeat->getRooms(), 'Repeat type 0');

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(1);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(4);
        $repeat->setRepeaterWeeks(1);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(4, $repeat->getRooms(), 'Repeat type 1');

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 2']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(2);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(4);
        $repeat->setRepeatMontly(1);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(4, $repeat->getRooms(), 'Repeat type 2');

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 3']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(3);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(4);
        $repeat->setRepeatMonthlyRelativeHowOften(1);
        $repeat->setRepatMonthRelativWeekday(1);
        $repeat->setRepatMonthRelativNumber(0);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(4, $repeat->getRooms(), 'Repeat type 3');

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 4']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(4);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(4);
        $repeat->setRepeatYearly(1);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(4, $repeat->getRooms(), 'Repeat type 4');

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 5']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(5);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(4);
        $repeat->setRepeatYearlyRelativeHowOften(1);
        $repeat->setRepeatYearlyRelativeWeekday(1);
        $repeat->setRepeatYearlyRelativeNumber(0);
        $repeat->setRepeatYearlyRelativeMonth(0);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(4, $repeat->getRooms(), 'Repeat type 5');
    }

    public function testSingleRepetationGeneratesOneRoomPerType(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(0);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(1);
        $repeat->setRepeaterDays(1);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(1, $repeat->getRooms(), 'Repeat type 0');

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(1);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(1);
        $repeat->setRepeaterWeeks(1);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(1, $repeat->getRooms(), 'Repeat type 1');

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 2']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(2);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(1);
        $repeat->setRepeatMontly(1);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(1, $repeat->getRooms(), 'Repeat type 2');

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 3']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(3);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(1);
        $repeat->setRepeatMonthlyRelativeHowOften(1);
        $repeat->setRepatMonthRelativWeekday(1);
        $repeat->setRepatMonthRelativNumber(0);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(1, $repeat->getRooms(), 'Repeat type 3');

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 4']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(4);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(1);
        $repeat->setRepeatYearly(1);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(1, $repeat->getRooms(), 'Repeat type 4');

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 5']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(5);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(1);
        $repeat->setRepeatYearlyRelativeHowOften(1);
        $repeat->setRepeatYearlyRelativeWeekday(1);
        $repeat->setRepeatYearlyRelativeNumber(0);
        $repeat->setRepeatYearlyRelativeMonth(0);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(1, $repeat->getRooms(), 'Repeat type 5');
    }

    public function testCheckDataRejectsMissingFieldsPerType(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $cases = [
            'daily without days' => [0, []],
            'weekly without weeks' => [1, []],
            'monthly without months' => [2, []],
            'monthly relative without number' => [3, ['setRepeatMonthlyRelativeHowOften' => 1, 'setRepatMonthRelativWeekday' => 1]],
            'monthly relative without weekday' => [3, ['setRepeatMonthlyRelativeHowOften' => 1, 'setRepatMonthRelativNumber' => 0]],
            'monthly relative without how often' => [3, ['setRepatMonthRelativWeekday' => 1, 'setRepatMonthRelativNumber' => 0]],
            'yearly without years' => [4, []],
            'yearly relative without month' => [5, ['setRepeatYearlyRelativeHowOften' => 1, 'setRepeatYearlyRelativeWeekday' => 1, 'setRepeatYearlyRelativeNumber' => 0]],
            'yearly relative empty' => [5, []],
        ];
        foreach ($cases as $label => [$repeatType, $fields]) {
            $repeat = new Repeat();
            $repeat->setRepeatType($repeatType);
            foreach ($fields as $setter => $value) {
                $repeat->{$setter}($value);
            }
            self::assertFalse($repeaterService->checkData($repeat), $label);
        }
    }

    public function testCheckDataAcceptsZeroOrdinals(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);

        $monthlyRelative = new Repeat();
        $monthlyRelative->setRepeatType(3);
        $monthlyRelative->setRepatMonthRelativNumber(0);
        $monthlyRelative->setRepatMonthRelativWeekday(0);
        $monthlyRelative->setRepeatMonthlyRelativeHowOften(1);
        self::assertTrue($repeaterService->checkData($monthlyRelative));

        $yearlyRelative = new Repeat();
        $yearlyRelative->setRepeatType(5);
        $yearlyRelative->setRepeatYearlyRelativeNumber(0);
        $yearlyRelative->setRepeatYearlyRelativeWeekday(0);
        $yearlyRelative->setRepeatYearlyRelativeMonth(0);
        $yearlyRelative->setRepeatYearlyRelativeHowOften(1);
        self::assertTrue($repeaterService->checkData($yearlyRelative));
    }

    public function testUnknownRepeatTypeGeneratesNoRooms(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(99);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        self::assertTrue($repeaterService->checkData($repeat));
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(0, $repeat->getRooms());
    }

    public function testGeneratedRoomUidsAreUnique(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(0);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(30);
        $repeat->setRepeaterDays(1);
        $repeaterService->createNewRepeater($repeat);

        $uids = [];
        $uidsReal = [];
        $uidsParticipant = [];
        $uidsModerator = [];
        foreach ($repeat->getRooms() as $generatedRoom) {
            self::assertNotNull($generatedRoom->getUid());
            self::assertNotNull($generatedRoom->getUidReal());
            self::assertNotNull($generatedRoom->getUidParticipant());
            self::assertNotNull($generatedRoom->getUidModerator());
            $uids[] = $generatedRoom->getUid();
            $uidsReal[] = $generatedRoom->getUidReal();
            $uidsParticipant[] = $generatedRoom->getUidParticipant();
            $uidsModerator[] = $generatedRoom->getUidModerator();
        }
        self::assertCount(30, array_unique($uids));
        self::assertCount(30, array_unique($uidsReal));
        self::assertCount(30, array_unique($uidsParticipant));
        self::assertCount(30, array_unique($uidsModerator));
    }

    public function testRepeatUntilIsIgnoredDuringSeriesCreation(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(0);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeaterDays(1);
        $repeat->setRepeatUntil(new \DateTimeImmutable('2021-01-16T15:00'));
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-01-15 15:00', '2021-01-16 15:00', '2021-01-17 15:00']);
    }

    public function testSeriesIcsHasOneRdatePerOccurrenceAndMatchingRecurrenceIds(): void
    {
        self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $repeat = new Repeat();
        $repeat->setRepeatType(0);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeaterDays(1);
        $repeat->setUid('series-ics-test');
        $repeat = $repeaterService->createNewRepeater($repeat);

        $captured = [];
        $mailer = $this->createMock(MailerService::class);
        $mailer->method('sendEmail')->willReturnCallback(function (...$args) use (&$captured) {
            $captured[] = $args;
            return true;
        });
        $service = $this->repeaterServiceWithMailer($mailer);
        $service->sendEMail(
            $repeat,
            'email/repeaterNew.html.twig',
            'subject',
            ['room' => $repeat->getPrototyp()],
            'REQUEST',
            [$repeat->getPrototyp()->getModerator()]
        );

        self::assertCount(1, $captured);
        $attachment = $captured[0][6];
        self::assertCount(1, $attachment);
        $ics = $attachment[0]['body'];

        $icsService = new IcsService();
        $expected = [];
        foreach ($repeat->getRooms() as $generatedRoom) {
            $expected[] = $icsService->toUtcZ($generatedRoom->getStartUtc());
        }
        self::assertCount(3, $expected);

        self::assertSame(1, substr_count($ics, 'RDATE:'));
        self::assertSame(3, substr_count($ics, 'RECURRENCE-ID:'));

        $rdateValue = null;
        foreach (preg_split('/\r\n/', $ics) as $line) {
            if (str_starts_with($line, 'RDATE:')) {
                $rdateValue = substr($line, strlen('RDATE:'));
                break;
            }
        }
        self::assertNotNull($rdateValue);
        self::assertSame($expected, explode(',', $rdateValue));
        foreach ($expected as $utc) {
            self::assertStringContainsString('RECURRENCE-ID:' . $utc, $ics);
        }
    }

    public function testRepeatedSeriesRegenerationStaysConsistent(): void
    {
        self::bootKernel();
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $repeatRepo = self::getContainer()->get(RepeatRepository::class);
        $room = $this->prepareRoom($roomRepo);
        foreach ($room->getUser() as $user) {
            $room->addPrototypeUser($user);
            $room->removeUser($user);
        }
        $manager->persist($room);
        $manager->flush();

        $repeat = new Repeat();
        $repeat->setRepeatType(0);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeaterDays(1);
        $repeat->setUid('regeneration-test');
        $repeat = $repeaterService->createNewRepeater($repeat);
        $manager->persist($repeat);
        $manager->flush();
        $repeaterService->addUserRepeat($repeat);
        $sequenceBefore = $repeat->getPrototyp()->getSequence();

        $repeat = $this->regenerateSeries($repeaterService, $manager, $repeat, '2021-02-01T10:00');
        $repeat = $repeatRepo->find($repeat->getId());
        $this->assertRoomStartDates($repeat, ['2021-02-01 10:00', '2021-02-02 10:00', '2021-02-03 10:00']);
        self::assertSame($sequenceBefore + 1, $repeat->getPrototyp()->getSequence());

        $repeat = $this->regenerateSeries($repeaterService, $manager, $repeat, '2021-03-01T10:00');
        $repeat = $repeatRepo->find($repeat->getId());
        $this->assertRoomStartDates($repeat, ['2021-03-01 10:00', '2021-03-02 10:00', '2021-03-03 10:00']);
        self::assertSame($sequenceBefore + 2, $repeat->getPrototyp()->getSequence());

        foreach ($repeat->getRooms() as $generatedRoom) {
            $roomCallerIds = [];
            self::assertCount(3, $generatedRoom->getCallerIds());
            foreach ($generatedRoom->getCallerIds() as $callerId) {
                $roomCallerIds[] = $callerId->getCallerId();
            }
            self::assertSame($roomCallerIds, array_values(array_unique($roomCallerIds)));
        }
        self::assertCount(3, $roomRepo->findBy(['repeater' => $repeat]));
    }
}
