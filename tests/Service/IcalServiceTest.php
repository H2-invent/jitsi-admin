<?php

namespace App\Tests\Service;

use App\Entity\Rooms;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\IcalService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class IcalServiceTest extends KernelTestCase
{
    public function testGetIcalReturnsCalendarForUser(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $service = $container->get(IcalService::class);
        $user = $container->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);

        $ical = $service->getIcal($user);

        self::assertIsString($ical);
        self::assertStringContainsString('BEGIN:VCALENDAR', $ical);
        self::assertStringContainsString('METHOD:', $ical);
        self::assertStringContainsString('END:VCALENDAR', $ical);
        self::assertIsArray($service->getRooms());
        self::assertNotEmpty($service->getRooms());
    }

    public function testInitRoomsFindsRoomsOfUser(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $service = $container->get(IcalService::class);
        $user = $container->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);

        $service->initRooms($user);

        $rooms = $service->getRooms();
        self::assertIsArray($rooms);
        self::assertNotEmpty($rooms);
        self::assertContainsOnlyInstancesOf(Rooms::class, $rooms);
    }

    public function testGetIcalStringRendersEventsAndReindexesRooms(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $service = $container->get(IcalService::class);
        $user = $container->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = $container->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $room->setHostUrl('https://meet.jit.si');

        $service->getIcal($user);
        $service->setRooms(['first' => $room, 7 => $room]);

        self::assertSame([0 => $room, 1 => $room], $service->getRooms());

        $ical = $service->getIcalString();

        self::assertStringContainsString('METHOD:PUBLISH', $ical);
        self::assertStringContainsString('SUMMARY:TestMeeting: 1', $ical);
        self::assertStringContainsString('DTSTART:', $ical);
        self::assertStringContainsString('DTEND:', $ical);
        self::assertStringContainsString('UID:', $ical);
        self::assertStringContainsString('DESCRIPTION:', $ical);
        self::assertStringContainsString('Testagenda:1', $ical);
        self::assertStringContainsString('URL:', $ical);
    }

    public function testSetRoomsWithEmptyArray(): void
    {
        self::bootKernel();
        $service = self::getContainer()->get(IcalService::class);

        $service->setRooms([]);

        self::assertSame([], $service->getRooms());
    }

    public function testSetRoomsWithTraversableLikeArrayReindexes(): void
    {
        self::bootKernel();
        $service = self::getContainer()->get(IcalService::class);

        $roomA = new Rooms();
        $roomB = new Rooms();
        $service->setRooms([5 => $roomA, 9 => $roomB]);

        self::assertSame([$roomA, $roomB], $service->getRooms());
    }
}
