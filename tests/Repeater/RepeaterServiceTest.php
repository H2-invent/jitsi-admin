<?php

/**
 * RepeaterService test overview (tests are listed below in execution order):
 *
 *  - testDailyRepeater: a daily series creates the expected three consecutive rooms.
 *  - testWeeklyRepeater: a weekly series creates rooms one week apart.
 *  - testMonthlyRepeater: a monthly series creates rooms one month apart.
 *  - testMonthlyRelativeRepeaterNextMonth: a "first Monday" series starts next month when this month's date has passed.
 *  - testMonthlyRelativeRepeaterThisMonth: a "first Monday" series includes this month when it has not passed.
 *  - testYearlyRepeater: a yearly series creates rooms one year apart.
 *  - testYearlyRelativeRepeaterNextYear: a "first Monday of January" series starts next year when this year's date has passed.
 *  - testYearlyRelativeRepeaterThisYear: a "first Monday of January" series includes this year when it has not passed.
 *  - testRepeatSendEmail: sending series invitations does not throw for derived or explicit recipients.
 *  - testChangeRepeaterRooms: editing start/duration regenerates rooms with shifted dates and kept participants.
 *  - testcreateCallerId: every generated room gets three caller IDs and a caller room.
 *  - testMonthlyRepeaterEndOfMonthOverflowsToNextMonth: documents month arithmetic overflow for a series starting on the 31st.
 *  - testDailyRepeaterWithMultiDayInterval: a daily series honours a multi-day interval.
 *  - testWeeklyRepeaterWithMultiWeekInterval: a weekly series honours a multi-week interval.
 *  - testMonthlyRepeaterWithMultiMonthInterval: a monthly series honours a multi-month interval.
 *  - testYearlyRepeaterWithMultiYearInterval: a yearly series honours a multi-year interval.
 *  - testMonthlyRelativeRepeaterWithMultiMonthInterval: a "first Monday" series honours a multi-month interval.
 *  - testYearlyRelativeRepeaterWithMultiYearInterval: a "first Monday of January" series honours a multi-year interval.
 *  - testMonthlyRelativeRepeaterLastWeekday: a "last Monday" series picks the last Monday of each month.
 *  - testMonthlyRelativeRepeaterFifthWeekdayOverflowsWhenAbsent: documents fifth-weekday overflow when a month has no fifth Monday.
 *  - testYearlyRelativeRepeaterLastWeekday: a "last Monday of January" series picks the last Monday each year.
 *  - testYearlyRelativeRepeaterFifthWeekdayOverflowsWhenAbsent: documents the yearly fifth-weekday overflow.
 *  - testYearlyRelativeRepeaterWithJulyMonth: a "first Monday of July" series works for a non-January month.
 *  - testYearlyRelativeRepeaterWithDecemberMonth: a "first Monday of December" series works for December.
 *  - testDailyRepeaterCrossesYearBoundary: a daily series rolls over the year boundary correctly.
 *  - testWeeklyRepeaterCrossesYearBoundary: a weekly series rolls over the year boundary correctly.
 *  - testMonthlyRepeaterCrossesYearBoundary: a monthly series rolls over the year boundary correctly.
 *  - testGeneratedRoomsEndDateMatchesStartPlusDuration: each room ends exactly duration minutes after it starts.
 *  - testDailyRepeaterPreservesWallClockAcrossDstSpringForward: a daily series keeps its wall-clock time over spring DST.
 *  - testDailyRepeaterPreservesWallClockAcrossDstAutumnBack: a daily series keeps its wall-clock time over autumn DST.
 *  - testEachRepeatTypeGeneratesExactlyRepetationRooms: all six types generate exactly the requested number of rooms.
 *  - testSingleRepetationGeneratesOneRoomPerType: all six types generate one room when repetitions is 1.
 *  - testCheckDataRejectsMissingFieldsPerType: validation rejects a series missing its type's required fields.
 *  - testCheckDataAcceptsZeroOrdinals: zero ordinals (First/Sunday/January) are accepted, only null is rejected.
 *  - testGeneratedRoomUidsAreUnique: generated rooms have unique uid, uidReal, uidParticipant and uidModerator.
 *  - testRepeatUntilIsIgnoredDuringSeriesCreation: documents that repeatUntil does not truncate a series.
 *  - testSeriesIcsHasOneRdatePerOccurrenceAndMatchingRecurrenceIds: the ICS has one RDATE list and a matching RECURRENCE-ID per occurrence.
 *  - testRepeatedSeriesRegenerationStaysConsistent: repeated edits keep three rooms, bump the sequence once and leave no orphans.
 *
 * Private helpers: prepareRoom(), changeStart(), assertRoomStartDates(), repeaterServiceWithMailer(), regenerateSeries().
 */

namespace App\Tests\Repeater;

use App\Enums\RepeatTypeEnum;
use App\Enums\RepeatNumberEnum;
use App\Enums\RepeatWeekdayEnum;
use App\Enums\RepeatMonthEnum;
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
    /**
     * A daily series (every 1 day, 3 repetitions) starting 2021-01-15 15:00 must create exactly three rooms on
     * 2021-01-15, 2021-01-16 and 2021-01-17, each keeping the prototype's three participants.
     */
    public function testDailyRepeater(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);

        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::DAILY);
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

    /**
     * A weekly series (every 1 week, 3 repetitions) starting 2021-01-15 15:00 must create rooms on
     * 2021-01-15, 2021-01-22 and 2021-01-29.
     */
    public function testWeeklyRepeater(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::WEEKLY);
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

    /**
     * A monthly series (every 1 month, 3 repetitions) starting 2021-01-15 15:00 must create rooms on the 15th of
     * January, February and March 2021.
     */
    public function testMonthlyRepeater(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::MONTHLY);
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

    /**
     * A "first Monday of the month" series starting 2021-01-15 must not schedule in January (that Monday already
     * passed), so the three rooms fall on 2021-02-01, 2021-03-01 and 2021-04-05.
     */
    public function testMonthlyRelativeRepeaterNextMonth(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::MONTHLY_RELATIVE);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatMonthlyRelativeHowOften(1);
        $repeat->setRepatMonthRelativWeekday(RepeatWeekdayEnum::MONDAY);
        $repeat->setRepatMonthRelativNumber(RepeatNumberEnum::FIRST);
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

    /**
     * A "first Monday of the month" series starting 2021-01-01 must include January itself, giving rooms on
     * 2021-01-04, 2021-02-01 and 2021-03-01.
     */
    public function testMonthlyRelativeRepeaterThisMonth(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $room = $this->changeStart($room, '2021-01-01T15:00');
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::MONTHLY_RELATIVE);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatMonthlyRelativeHowOften(1);
        $repeat->setRepatMonthRelativWeekday(RepeatWeekdayEnum::MONDAY);
        $repeat->setRepatMonthRelativNumber(RepeatNumberEnum::FIRST);
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

    /**
     * A yearly series (every 1 year, 3 repetitions) starting 2021-01-15 must create rooms on 2021-01-15,
     * 2022-01-15 and 2023-01-15.
     */
    public function testYearlyRepeater(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::YEARLY);
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

    /**
     * A "first Monday of January" series starting 2021-01-15 must skip 2021 (the first Monday already passed) and
     * create rooms on 2022-01-03, 2023-01-02 and 2024-01-01.
     */
    public function testYearlyRelativeRepeaterNextYear(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::YEARLY_RELATIVE);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatYearlyRelativeHowOften(1);
        $repeat->setRepeatYearlyRelativeWeekday(RepeatWeekdayEnum::MONDAY);
        $repeat->setRepeatYearlyRelativeNumber(RepeatNumberEnum::FIRST);
        $repeat->setRepeatYearlyRelativeMonth(RepeatMonthEnum::JANUARY);
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

    /**
     * A "first Monday of January" series starting 2021-01-01 must include 2021 itself, giving rooms on
     * 2021-01-04, 2022-01-03 and 2023-01-02.
     */
    public function testYearlyRelativeRepeaterThisYear(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $room = $this->changeStart($room, '2021-01-01T15:00');
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::YEARLY_RELATIVE);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatYearlyRelativeHowOften(1);
        $repeat->setRepeatYearlyRelativeWeekday(RepeatWeekdayEnum::MONDAY);
        $repeat->setRepeatYearlyRelativeNumber(RepeatNumberEnum::FIRST);
        $repeat->setRepeatYearlyRelativeMonth(RepeatMonthEnum::JANUARY);
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


    /**
     * Sending the series invitation for a 3-room daily series must not throw, both when the recipient list is
     * derived from the prototype users and when it is passed explicitly.
     */
    public function testRepeatSendEmail(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::DAILY);
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

    /**
     * Editing a series to a new start (2021-01-20 18:00) and duration (90 min) must regenerate exactly three rooms
     * with the shifted dates, keep the participants and caller IDs, and return the "edited" confirmation text.
     */
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
        $repeat->setRepeatType(RepeatTypeEnum::DAILY);
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

    /**
     * Loads "TestMeeting: 0", moves its start to 2021-01-15 15:00 and adds moderator/user attributes so generated
     * series have the expected participants. Returns the prepared prototype room.
     */
    private function prepareRoom(RoomsRepository $roomsRepository): Rooms
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

    /**
     * Sets a room's start (optionally in a given timezone) and recalculates its end date from its duration.
     * Returns the room.
     */
    private function changeStart(Rooms $rooms, string $startDate, ?string $timeZone = null): Rooms
    {
        $rooms->setStart(new \DateTimeImmutable($startDate, $timeZone ? new \DateTimeZone($timeZone) : null));
        $endDate = clone $rooms->getStart();
        $endDate = $endDate->modify('+' . $rooms->getDuration() . 'min');
        $rooms->setEnddate($endDate);
        return $rooms;
    }

    /**
     * Asserts the series has exactly the expected number of rooms and that each room starts at the matching
     * "Y-m-d H:i" value in order.
     *
     * @param array<int, string> $expected
     */
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

    /**
     * Builds a RepeaterService with the real collaborators but a supplied (mock) mailer, so tests can capture the
     * generated ICS attachment.
     */
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

    /**
     * Emulates a fresh edit request: clears the entity manager, reloads the repeater, applies the new start and
     * runs replaceRooms. Returns the reloaded repeater.
     */
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

    /**
     * Creating caller IDs for a 3-room yearly-relative series must attach exactly three caller IDs and a caller
     * room to every generated room.
     */
    public function testcreateCallerId(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $room = $this->changeStart($room, '2021-01-01T15:00');
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::YEARLY_RELATIVE);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatYearlyRelativeHowOften(1);
        $repeat->setRepeatYearlyRelativeWeekday(RepeatWeekdayEnum::MONDAY);
        $repeat->setRepeatYearlyRelativeNumber(RepeatNumberEnum::FIRST);
        $repeat->setRepeatYearlyRelativeMonth(RepeatMonthEnum::JANUARY);
        self::assertTrue($repeaterService->checkData($repeat));
        $repeaterService->createNewRepeater($repeat);
        $repeaterService->createNewCaller($repeat);
        foreach ($repeat->getRooms() as $data) {
            self::assertEquals(3, sizeof($data->getCallerIds()));
            self::assertNotNull($data->getCallerRoom());
        }
    }

    /**
     * Documents PHP month arithmetic for a series starting on the 31st: 2021-01-31 plus one month overflows to
     * 2021-03-03, then 2021-04-03. The test locks in this actual behaviour rather than a clamped month end.
     */
    public function testMonthlyRepeaterEndOfMonthOverflowsToNextMonth(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $room = $this->changeStart($room, '2021-01-31T15:00');
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::MONTHLY);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatMontly(1);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-01-31 15:00', '2021-03-03 15:00', '2021-04-03 15:00']);
    }

    /**
     * A daily series with a 3-day interval starting 2021-01-15 must create rooms on 2021-01-15, 2021-01-18 and
     * 2021-01-21.
     */
    public function testDailyRepeaterWithMultiDayInterval(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::DAILY);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeaterDays(3);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-01-15 15:00', '2021-01-18 15:00', '2021-01-21 15:00']);
    }

    /**
     * A weekly series with a 2-week interval starting 2021-01-15 must create rooms on 2021-01-15, 2021-01-29 and
     * 2021-02-12.
     */
    public function testWeeklyRepeaterWithMultiWeekInterval(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::WEEKLY);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeaterWeeks(2);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-01-15 15:00', '2021-01-29 15:00', '2021-02-12 15:00']);
    }

    /**
     * A monthly series with a 3-month interval starting 2021-01-15 must create rooms on 2021-01-15, 2021-04-15 and
     * 2021-07-15.
     */
    public function testMonthlyRepeaterWithMultiMonthInterval(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::MONTHLY);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatMontly(3);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-01-15 15:00', '2021-04-15 15:00', '2021-07-15 15:00']);
    }

    /**
     * A yearly series with a 2-year interval starting 2021-01-15 must create rooms on 2021-01-15, 2023-01-15 and
     * 2025-01-15.
     */
    public function testYearlyRepeaterWithMultiYearInterval(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::YEARLY);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatYearly(2);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-01-15 15:00', '2023-01-15 15:00', '2025-01-15 15:00']);
    }

    /**
     * A "first Monday" series with a 2-month interval starting 2021-01-15 must create four rooms on 2021-02-01,
     * 2021-04-05, 2021-06-07 and 2021-08-02.
     */
    public function testMonthlyRelativeRepeaterWithMultiMonthInterval(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::MONTHLY_RELATIVE);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(4);
        $repeat->setRepeatMonthlyRelativeHowOften(2);
        $repeat->setRepatMonthRelativWeekday(RepeatWeekdayEnum::MONDAY);
        $repeat->setRepatMonthRelativNumber(RepeatNumberEnum::FIRST);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-02-01 15:00', '2021-04-05 15:00', '2021-06-07 15:00', '2021-08-02 15:00']);
    }

    /**
     * A "first Monday of January" series with a 2-year interval starting 2021-01-15 must create rooms on
     * 2022-01-03, 2024-01-01 and 2026-01-05.
     */
    public function testYearlyRelativeRepeaterWithMultiYearInterval(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::YEARLY_RELATIVE);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatYearlyRelativeHowOften(2);
        $repeat->setRepeatYearlyRelativeWeekday(RepeatWeekdayEnum::MONDAY);
        $repeat->setRepeatYearlyRelativeNumber(RepeatNumberEnum::FIRST);
        $repeat->setRepeatYearlyRelativeMonth(RepeatMonthEnum::JANUARY);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2022-01-03 15:00', '2024-01-01 15:00', '2026-01-05 15:00']);
    }

    /**
     * A "last Monday of the month" series starting 2021-01-15 must create rooms on 2021-01-25, 2021-02-22 and
     * 2021-03-29.
     */
    public function testMonthlyRelativeRepeaterLastWeekday(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::MONTHLY_RELATIVE);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatMonthlyRelativeHowOften(1);
        $repeat->setRepatMonthRelativWeekday(RepeatWeekdayEnum::MONDAY);
        $repeat->setRepatMonthRelativNumber(RepeatNumberEnum::LAST);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-01-25 15:00', '2021-02-22 15:00', '2021-03-29 15:00']);
    }

    /**
     * Documents that a "fifth Monday" series overflows into the next month when the month has no fifth Monday:
     * starting 2021-01-15 the rooms fall on 2021-02-01, 2021-03-01 and 2021-05-03.
     */
    public function testMonthlyRelativeRepeaterFifthWeekdayOverflowsWhenAbsent(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::MONTHLY_RELATIVE);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatMonthlyRelativeHowOften(1);
        $repeat->setRepatMonthRelativWeekday(RepeatWeekdayEnum::MONDAY);
        $repeat->setRepatMonthRelativNumber(RepeatNumberEnum::FIFTH);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-02-01 15:00', '2021-03-01 15:00', '2021-05-03 15:00']);
    }

    /**
     * A "last Monday of January" series starting 2021-01-15 must create rooms on 2021-01-25, 2022-01-31 and
     * 2023-01-30.
     */
    public function testYearlyRelativeRepeaterLastWeekday(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::YEARLY_RELATIVE);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatYearlyRelativeHowOften(1);
        $repeat->setRepeatYearlyRelativeWeekday(RepeatWeekdayEnum::MONDAY);
        $repeat->setRepeatYearlyRelativeNumber(RepeatNumberEnum::LAST);
        $repeat->setRepeatYearlyRelativeMonth(RepeatMonthEnum::JANUARY);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-01-25 15:00', '2022-01-31 15:00', '2023-01-30 15:00']);
    }

    /**
     * Documents the yearly "fifth Monday of January" overflow: the first occurrence lands on 2021-02-01, followed
     * by 2022-01-31 and 2023-01-30.
     */
    public function testYearlyRelativeRepeaterFifthWeekdayOverflowsWhenAbsent(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::YEARLY_RELATIVE);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatYearlyRelativeHowOften(1);
        $repeat->setRepeatYearlyRelativeWeekday(RepeatWeekdayEnum::MONDAY);
        $repeat->setRepeatYearlyRelativeNumber(RepeatNumberEnum::FIFTH);
        $repeat->setRepeatYearlyRelativeMonth(RepeatMonthEnum::JANUARY);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-02-01 15:00', '2022-01-31 15:00', '2023-01-30 15:00']);
    }

    /**
     * A "first Monday of July" series starting 2021-01-15 must create rooms on 2021-07-05, 2022-07-04 and
     * 2023-07-03, confirming months other than January are parsed correctly.
     */
    public function testYearlyRelativeRepeaterWithJulyMonth(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::YEARLY_RELATIVE);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatYearlyRelativeHowOften(1);
        $repeat->setRepeatYearlyRelativeWeekday(RepeatWeekdayEnum::MONDAY);
        $repeat->setRepeatYearlyRelativeNumber(RepeatNumberEnum::FIRST);
        $repeat->setRepeatYearlyRelativeMonth(RepeatMonthEnum::JULY);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-07-05 15:00', '2022-07-04 15:00', '2023-07-03 15:00']);
    }

    /**
     * A "first Monday of December" series starting 2021-01-15 must create rooms on 2021-12-06, 2022-12-05 and
     * 2023-12-04.
     */
    public function testYearlyRelativeRepeaterWithDecemberMonth(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::YEARLY_RELATIVE);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatYearlyRelativeHowOften(1);
        $repeat->setRepeatYearlyRelativeWeekday(RepeatWeekdayEnum::MONDAY);
        $repeat->setRepeatYearlyRelativeNumber(RepeatNumberEnum::FIRST);
        $repeat->setRepeatYearlyRelativeMonth(RepeatMonthEnum::DECEMBER);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-12-06 15:00', '2022-12-05 15:00', '2023-12-04 15:00']);
    }

    /**
     * A daily series starting 2021-12-30 must roll over the year correctly: 2021-12-30, 2021-12-31, 2022-01-01
     * and 2022-01-02.
     */
    public function testDailyRepeaterCrossesYearBoundary(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $room = $this->changeStart($room, '2021-12-30T15:00');
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::DAILY);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(4);
        $repeat->setRepeaterDays(1);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-12-30 15:00', '2021-12-31 15:00', '2022-01-01 15:00', '2022-01-02 15:00']);
    }

    /**
     * A weekly series starting 2021-12-20 must roll over the year correctly: 2021-12-20, 2021-12-27 and
     * 2022-01-03.
     */
    public function testWeeklyRepeaterCrossesYearBoundary(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $room = $this->changeStart($room, '2021-12-20T15:00');
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::WEEKLY);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeaterWeeks(1);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-12-20 15:00', '2021-12-27 15:00', '2022-01-03 15:00']);
    }

    /**
     * A monthly series starting 2021-11-15 must roll over the year correctly: 2021-11-15, 2021-12-15 and
     * 2022-01-15.
     */
    public function testMonthlyRepeaterCrossesYearBoundary(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $room = $this->changeStart($room, '2021-11-15T15:00');
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::MONTHLY);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeatMontly(1);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-11-15 15:00', '2021-12-15 15:00', '2022-01-15 15:00']);
    }

    /**
     * Every generated room's end time must equal its start time plus the prototype duration (60 minutes here).
     */
    public function testGeneratedRoomsEndDateMatchesStartPlusDuration(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::WEEKLY);
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

    /**
     * A daily series across the spring DST switch (Europe/Berlin, 2021-03-28) must keep the 15:00 wall-clock time
     * on 2021-03-27, 2021-03-28 and 2021-03-29.
     */
    public function testDailyRepeaterPreservesWallClockAcrossDstSpringForward(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $room = $this->changeStart($room, '2021-03-27T15:00', 'Europe/Berlin');
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::DAILY);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeaterDays(1);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-03-27 15:00', '2021-03-28 15:00', '2021-03-29 15:00']);
    }

    /**
     * A daily series across the autumn DST switch (Europe/Berlin, 2021-10-31) must keep the 15:00 wall-clock time
     * on 2021-10-30, 2021-10-31 and 2021-11-01.
     */
    public function testDailyRepeaterPreservesWallClockAcrossDstAutumnBack(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $room = $this->changeStart($room, '2021-10-30T15:00', 'Europe/Berlin');
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::DAILY);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeaterDays(1);
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-10-30 15:00', '2021-10-31 15:00', '2021-11-01 15:00']);
    }

    /**
     * For all six repeat types, a series with 4 repetitions must produce exactly 4 generated rooms
     * (the prototype room is not counted).
     */
    public function testEachRepeatTypeGeneratesExactlyRepetationRooms(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::DAILY);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(4);
        $repeat->setRepeaterDays(1);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(4, $repeat->getRooms(), 'Repeat type 0');

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::WEEKLY);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(4);
        $repeat->setRepeaterWeeks(1);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(4, $repeat->getRooms(), 'Repeat type 1');

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 2']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::MONTHLY);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(4);
        $repeat->setRepeatMontly(1);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(4, $repeat->getRooms(), 'Repeat type 2');

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 3']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::MONTHLY_RELATIVE);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(4);
        $repeat->setRepeatMonthlyRelativeHowOften(1);
        $repeat->setRepatMonthRelativWeekday(RepeatWeekdayEnum::MONDAY);
        $repeat->setRepatMonthRelativNumber(RepeatNumberEnum::FIRST);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(4, $repeat->getRooms(), 'Repeat type 3');

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 4']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::YEARLY);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(4);
        $repeat->setRepeatYearly(1);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(4, $repeat->getRooms(), 'Repeat type 4');

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 5']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::YEARLY_RELATIVE);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(4);
        $repeat->setRepeatYearlyRelativeHowOften(1);
        $repeat->setRepeatYearlyRelativeWeekday(RepeatWeekdayEnum::MONDAY);
        $repeat->setRepeatYearlyRelativeNumber(RepeatNumberEnum::FIRST);
        $repeat->setRepeatYearlyRelativeMonth(RepeatMonthEnum::JANUARY);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(4, $repeat->getRooms(), 'Repeat type 5');
    }

    /**
     * For all six repeat types, a series with a single repetition must produce exactly 1 generated room.
     */
    public function testSingleRepetationGeneratesOneRoomPerType(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::DAILY);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(1);
        $repeat->setRepeaterDays(1);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(1, $repeat->getRooms(), 'Repeat type 0');

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::WEEKLY);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(1);
        $repeat->setRepeaterWeeks(1);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(1, $repeat->getRooms(), 'Repeat type 1');

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 2']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::MONTHLY);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(1);
        $repeat->setRepeatMontly(1);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(1, $repeat->getRooms(), 'Repeat type 2');

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 3']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::MONTHLY_RELATIVE);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(1);
        $repeat->setRepeatMonthlyRelativeHowOften(1);
        $repeat->setRepatMonthRelativWeekday(RepeatWeekdayEnum::MONDAY);
        $repeat->setRepatMonthRelativNumber(RepeatNumberEnum::FIRST);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(1, $repeat->getRooms(), 'Repeat type 3');

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 4']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::YEARLY);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(1);
        $repeat->setRepeatYearly(1);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(1, $repeat->getRooms(), 'Repeat type 4');

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 5']);
        self::assertNotNull($room);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::YEARLY_RELATIVE);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(1);
        $repeat->setRepeatYearlyRelativeHowOften(1);
        $repeat->setRepeatYearlyRelativeWeekday(RepeatWeekdayEnum::MONDAY);
        $repeat->setRepeatYearlyRelativeNumber(RepeatNumberEnum::FIRST);
        $repeat->setRepeatYearlyRelativeMonth(RepeatMonthEnum::JANUARY);
        $repeaterService->createNewRepeater($repeat);
        self::assertCount(1, $repeat->getRooms(), 'Repeat type 5');
    }

    /**
     * Validation must reject a series when the interval/ordinal fields required by its type are missing: daily
     * days, weekly weeks, monthly months, monthly-relative number/weekday/how-often, yearly years and
     * yearly-relative month.
     */
    public function testCheckDataRejectsMissingFieldsPerType(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $cases = [
            'daily without days' => [RepeatTypeEnum::DAILY, []],
            'weekly without weeks' => [RepeatTypeEnum::WEEKLY, []],
            'monthly without months' => [RepeatTypeEnum::MONTHLY, []],
            'monthly relative without number' => [RepeatTypeEnum::MONTHLY_RELATIVE, ['setRepeatMonthlyRelativeHowOften' => 1, 'setRepatMonthRelativWeekday' => RepeatWeekdayEnum::MONDAY]],
            'monthly relative without weekday' => [RepeatTypeEnum::MONTHLY_RELATIVE, ['setRepeatMonthlyRelativeHowOften' => 1, 'setRepatMonthRelativNumber' => RepeatNumberEnum::FIRST]],
            'monthly relative without how often' => [RepeatTypeEnum::MONTHLY_RELATIVE, ['setRepatMonthRelativWeekday' => RepeatWeekdayEnum::MONDAY, 'setRepatMonthRelativNumber' => RepeatNumberEnum::FIRST]],
            'yearly without years' => [RepeatTypeEnum::YEARLY, []],
            'yearly relative without month' => [RepeatTypeEnum::YEARLY_RELATIVE, ['setRepeatYearlyRelativeHowOften' => 1, 'setRepeatYearlyRelativeWeekday' => RepeatWeekdayEnum::MONDAY, 'setRepeatYearlyRelativeNumber' => RepeatNumberEnum::FIRST]],
            'yearly relative empty' => [RepeatTypeEnum::YEARLY_RELATIVE, []],
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

    /**
     * Zero is a valid ordinal value (0 = First / Sunday), so monthly- and yearly-relative series using 0 must pass
     * validation; only null is rejected.
     */
    public function testCheckDataAcceptsZeroOrdinals(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);

        $monthlyRelative = new Repeat();
        $monthlyRelative->setRepeatType(RepeatTypeEnum::MONTHLY_RELATIVE);
        $monthlyRelative->setRepatMonthRelativNumber(RepeatNumberEnum::FIRST);
        $monthlyRelative->setRepatMonthRelativWeekday(RepeatWeekdayEnum::SUNDAY);
        $monthlyRelative->setRepeatMonthlyRelativeHowOften(1);
        self::assertTrue($repeaterService->checkData($monthlyRelative));

        $yearlyRelative = new Repeat();
        $yearlyRelative->setRepeatType(RepeatTypeEnum::YEARLY_RELATIVE);
        $yearlyRelative->setRepeatYearlyRelativeNumber(RepeatNumberEnum::FIRST);
        $yearlyRelative->setRepeatYearlyRelativeWeekday(RepeatWeekdayEnum::SUNDAY);
        $yearlyRelative->setRepeatYearlyRelativeMonth(RepeatMonthEnum::JANUARY);
        $yearlyRelative->setRepeatYearlyRelativeHowOften(1);
        self::assertTrue($repeaterService->checkData($yearlyRelative));
    }

    /**
     * A 30-room daily series must give every generated room a distinct uid, uidReal, uidParticipant and
     * uidModerator (guards against identifier collisions between clones).
     */
    public function testGeneratedRoomUidsAreUnique(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::DAILY);
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

    /**
     * Documents that the unused repeatUntil field does not truncate a series: a 3-repetition daily series still
     * creates all 3 rooms even when repeatUntil is set to the second occurrence.
     */
    public function testRepeatUntilIsIgnoredDuringSeriesCreation(): void
    {
        self::bootKernel();
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::DAILY);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeaterDays(1);
        $repeat->setRepeatUntil(new \DateTimeImmutable('2021-01-16T15:00'));
        $repeaterService->createNewRepeater($repeat);
        $this->assertRoomStartDates($repeat, ['2021-01-15 15:00', '2021-01-16 15:00', '2021-01-17 15:00']);
    }

    /**
     * The generated ICS for a 3-room series must contain exactly one RDATE line listing all three UTC start times
     * and one RECURRENCE-ID per occurrence that matches those RDATE values.
     */
    public function testSeriesIcsHasOneRdatePerOccurrenceAndMatchingRecurrenceIds(): void
    {
        self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $this->prepareRoom($roomRepo);
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $repeat = new Repeat();
        $repeat->setRepeatType(RepeatTypeEnum::DAILY);
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

    /**
     * Regenerating a series twice (each edit shifting the start) must keep exactly three rooms for the repeater,
     * advance the prototype sequence by one per edit, and leave each room with three unique caller IDs and no
     * orphaned rooms.
     */
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
        $repeat->setRepeatType(RepeatTypeEnum::DAILY);
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
