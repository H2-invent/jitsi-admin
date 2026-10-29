<?php

namespace App\Tests\Repeater;

use App\Entity\Repeat;
use App\Repository\RoomsRepository;
use App\Service\RepeaterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The number of created appointments is limited by the laf_max_repeat setting.
 */
class RepeaterMaxRepeatTest extends KernelTestCase
{
    public function testCreateNewRepeaterRefusesMoreThanLafMaxRepeat(): void
    {
        $maxRepeat = (int)self::getContainer()->get('parameter_bag')->get('laf_max_repeat');
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $room->setStart((new \DateTime())->modify('+1 day')->setTime(10, 0));
        $room->setEnddate((clone $room->getStart())->modify('+60 minutes'));
        $manager->persist($room);
        $manager->flush();

        $repeat = new Repeat();
        $repeat->setRepeatType(0);
        $repeat->setRepeaterDays(7);
        $repeat->setRepetation($maxRepeat + 1);
        $repeat->setUid(md5(uniqid()));
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $manager->persist($repeat);
        $manager->flush();

        $this->expectException(\InvalidArgumentException::class);
        self::getContainer()->get(RepeaterService::class)->createNewRepeater($repeat);
    }

    public function testCreateNewRepeaterAcceptsLafMaxRepeat(): void
    {
        $maxRepeat = (int)self::getContainer()->get('parameter_bag')->get('laf_max_repeat');
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $room->setStart((new \DateTime())->modify('+1 day')->setTime(10, 0));
        $room->setEnddate((clone $room->getStart())->modify('+60 minutes'));
        $manager->persist($room);
        $manager->flush();

        $repeat = new Repeat();
        $repeat->setRepeatType(0);
        $repeat->setRepeaterDays(7);
        $repeat->setRepetation($maxRepeat);
        $repeat->setUid(md5(uniqid()));
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $manager->persist($repeat);
        $manager->flush();

        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $repeat = $repeaterService->createNewRepeater($repeat);
        self::assertCount($maxRepeat, $repeat->getRooms());
    }
}
