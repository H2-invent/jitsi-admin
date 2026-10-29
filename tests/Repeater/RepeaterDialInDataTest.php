<?php

namespace App\Tests\Repeater;

use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\caller\CallerPrepareService;
use App\Service\RepeaterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Every appointment of a series has to carry its own dial-in room number and its own personal PIN.
 */
class RepeaterDialInDataTest extends KernelTestCase
{
    public function testEachAppointmentHasItsOwnRoomNumber(): void
    {
        [$prototype, $appointments] = $this->createSeries();

        self::assertNull($prototype->getCallerRoom(), 'the prototype of a series has no dial-in number of its own');
        self::assertCount(3, $appointments);
        $numbers = [];
        foreach ($appointments as $appointment) {
            self::assertNotNull($appointment->getCallerRoom());
            $numbers[] = $appointment->getCallerRoom()->getCallerId();
        }
        self::assertCount(3, array_unique($numbers), 'every appointment needs its own dial-in number');
    }

    public function testEachAppointmentHasItsOwnPersonalPin(): void
    {
        [, $appointments] = $this->createSeries();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local2.de']);

        $pins = [];
        foreach ($appointments as $appointment) {
            $appointmentPins = [];
            foreach ($appointment->getCallerIds() as $callerId) {
                if ($callerId->getUser() === $user) {
                    $appointmentPins[] = $callerId->getCallerId();
                }
            }
            self::assertCount(1, $appointmentPins, 'exactly one PIN per user and appointment');
            $pins[] = $appointmentPins[0];
        }
        self::assertCount(3, array_unique($pins), 'the personal PIN has to be unique for every appointment');
    }

    public function testGeneratingTheCallerIdsAgainDoesNotDuplicate(): void
    {
        [$prototype, $appointments] = $this->createSeries();
        $repeat = $prototype->getRepeaterProtoype();
        self::assertNotNull($repeat);

        self::getContainer()->get(CallerPrepareService::class)->createUserCallerIDforRepeater($repeat);

        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->clear();
        foreach ($appointments as $appointment) {
            $fresh = self::getContainer()->get(RoomsRepository::class)->find($appointment->getId());
            self::assertCount(3, $fresh->getCallerIds(), 'running the caller preparation again must not create duplicate PINs');
        }
    }

    /**
     * @return array{0: Rooms, 1: Rooms[]}
     */
    private function createSeries(): array
    {
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $room->setStart((new \DateTime())->modify('+1 day')->setTime(10, 0));
        $room->setEnddate((clone $room->getStart())->modify('+60 minutes'));
        $manager->persist($room);
        $manager->flush();

        $repeat = new Repeat();
        $repeat->setRepeatType(0);
        $repeat->setRepeaterDays(7);
        $repeat->setRepetation(3);
        $repeat->setUid(md5(uniqid()));
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $manager->persist($repeat);
        $manager->flush();

        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $repeaterService->cleanRepeater($repeat);
        $repeaterService->createNewRepeater($repeat);
        $repeaterService->addUserRepeat($repeat);

        $manager->clear();
        $prototype = $roomRepo->find($room->getId());
        $appointments = [];
        foreach ($roomRepo->findBy(['name' => 'TestMeeting: 0'], ['start' => 'ASC']) as $candidate) {
            if ($candidate->getRepeater() !== null) {
                $appointments[] = $candidate;
            }
        }

        return [$prototype, $appointments];
    }
}
