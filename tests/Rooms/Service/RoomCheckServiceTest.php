<?php

namespace App\Tests\Rooms\Service;

use App\Entity\Rooms;
use App\Service\RoomCheckService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RoomCheckServiceTest extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $checkService = self::getContainer()->get(RoomCheckService::class);
        $room = new Rooms();
//        $room->setStart(new \DateTime());
//        $room->setDuration(60);
//        $room->setPersistantRoom(true);
        $error = [];
        $checkService->checkRoom($room, $error);
        self::assertEquals(['Fehler, bitte das Startdatum eingeben.', 'Fehler, bitte den Namen angeben.'], $error);
        $room->setName('test123');
        $error = [];
        $checkService->checkRoom($room, $error);
        self::assertEquals(['Fehler, bitte das Startdatum eingeben.'], $error);
        $room->setStart(new \DateTime());
        $room->setDuration(60);
        $error = [];
        $checkService->checkRoom($room, $error);
        self::assertEquals([], $error);
        $error = [];
        $room->setStart((new \DateTime())->modify('-30min'));
        $room->setDuration(60);
        $checkService->checkRoom($room, $error);
        self::assertEquals([], $error);
        $error = [];
        $room->setStart((new \DateTime())->modify('-70min'));
        $room->setDuration(60);
        $checkService->checkRoom($room, $error);
        self::assertEquals(['Fehler, das Startdatum und das Enddatum liegen in der Vergangenheit.'], $error);

        $error = [];
        $room->setStart((new \DateTime()));
        $room->setDuration(60);
        $checkService->checkRoom($room, $error);
        self::assertEquals([], $error);
        self::assertEquals((new \DateTime())->modify('+60min')->format('H:i:s'), $room->getEnddate()->format('H:i:s'));
        self::assertStringStartsNotWith('test123-', $room->getUid());
        self::assertStringStartsNotWith('test123-', (string)$room->getSlug());
        $error = [];
        $room->setPersistantRoom(true);
        $checkService->checkRoom($room, $error);
        self::assertEquals([], $error);
        self::assertNull($room->getStartUtc());
        self::assertNull($room->getStartTimestamp());
        self::assertNull($room->getEnddate());
        self::assertNull($room->getEndDateUtc());
        self::assertNull($room->getEndTimestamp());
        self::assertStringStartsWith('test123-', $room->getUid());
        self::assertStringStartsWith('test123-', $room->getSlug());


        $nowGermany = new \DateTime('now', new \DateTimeZone('Europe/Berlin'));
        $room = new  Rooms();
        $room->setName('test')
            ->setStart((clone $nowGermany)->modify('- 3 hours'))
            ->setTimeZone('America/Toronto')
            ->setDuration(60);
        $error = [];
        $checkService->checkRoom($room, $error);
        self::assertEquals([], $error);


        $nowGermany = new \DateTime('now', new \DateTimeZone('Europe/Berlin'));
        $room = new  Rooms();
        $room->setName('test')
            ->setStart((clone $nowGermany)->modify('- 3 hours'))
            ->setTimeZone('Europe/Berlin')
            ->setDuration(60);
        $error = [];
        $checkService->checkRoom($room, $error);
        self::assertEquals(['Fehler, das Startdatum und das Enddatum liegen in der Vergangenheit.'], $error);

        $nowGermany = new \DateTime('now', new \DateTimeZone('Europe/Berlin'));
        $room = new  Rooms();
        $room->setName('test')
            ->setStart((clone $nowGermany)->modify('- 8 hours'))
            ->setTimeZone('Europe/Berlin')
            ->setDuration(60);
        $error = [];
        $checkService->checkRoom($room, $error);
        self::assertEquals(['Fehler, das Startdatum und das Enddatum liegen in der Vergangenheit.'], $error);


        $nowGermany = new \DateTime('now', new \DateTimeZone('Europe/Berlin'));
        $room = new  Rooms();
        $room->setName('test')
            ->setStart((clone $nowGermany)->modify('- 3 hours'))
            ->setTimeZone('Europe/Berlin')
            ->setPersistantRoom(true)
            ->setDuration(60);
        $error = [];
        $checkService->checkRoom($room, $error);
        self::assertEquals([], $error);
    }

    public function testSetUTCTimeSetsUtcValuesWhenDatesAreSet(): void
    {
        $room = new Rooms();
        $room->setTimeZone('Europe/Berlin');
        $start = new \DateTime('2026-01-15T10:00:00', new \DateTimeZone('Europe/Berlin'));
        $end = new \DateTime('2026-01-15T11:00:00', new \DateTimeZone('Europe/Berlin'));
        $room->setStart($start);
        $room->setEnddate($end);

        self::assertNotNull($room->getStartUtc());
        self::assertNotNull($room->getEndDateUtc());
        self::assertNotNull($room->getStartTimestamp());
        self::assertNotNull($room->getEndTimestamp());
        self::assertEquals('2026-01-15 09:00:00', $room->getStartUtc()->format('Y-m-d H:i:s'));
        self::assertEquals('2026-01-15 10:00:00', $room->getEndDateUtc()->format('Y-m-d H:i:s'));
    }

    public function testSetUTCTimeNullifiesUtcWhenStartIsNull(): void
    {
        $room = new Rooms();
        $room->setTimeZone('Europe/Berlin');
        $start = new \DateTime('2026-01-15T10:00:00', new \DateTimeZone('Europe/Berlin'));
        $end = new \DateTime('2026-01-15T11:00:00', new \DateTimeZone('Europe/Berlin'));
        $room->setStart($start);
        $room->setEnddate($end);

        self::assertNotNull($room->getStartUtc());

        $room->setStart(null);

        self::assertNull($room->getStartUtc());
        self::assertNull($room->getStartTimestamp());
        self::assertNotNull($room->getEndDateUtc(), 'EndDateUtc should remain set');
    }

    public function testSetUTCTimeNullifiesUtcWhenEnddateIsNull(): void
    {
        $room = new Rooms();
        $room->setTimeZone('Europe/Berlin');
        $start = new \DateTime('2026-01-15T10:00:00', new \DateTimeZone('Europe/Berlin'));
        $end = new \DateTime('2026-01-15T11:00:00', new \DateTimeZone('Europe/Berlin'));
        $room->setStart($start);
        $room->setEnddate($end);

        self::assertNotNull($room->getEndDateUtc());

        $room->setEnddate(null);

        self::assertNull($room->getEndDateUtc());
        self::assertNull($room->getEndTimestamp());
        self::assertNotNull($room->getStartUtc(), 'StartUtc should remain set');
    }

    public function testSetUTCTimeNullifiesBothWhenBothDatesAreNull(): void
    {
        $room = new Rooms();
        $room->setTimeZone('Europe/Berlin');
        $start = new \DateTime('2026-01-15T10:00:00', new \DateTimeZone('Europe/Berlin'));
        $end = new \DateTime('2026-01-15T11:00:00', new \DateTimeZone('Europe/Berlin'));
        $room->setStart($start);
        $room->setEnddate($end);

        $room->setStart(null);
        $room->setEnddate(null);

        self::assertNull($room->getStartUtc());
        self::assertNull($room->getStartTimestamp());
        self::assertNull($room->getEndDateUtc());
        self::assertNull($room->getEndTimestamp());
    }

    public function testSetUTCTimeRecomputesWhenDateIsSetAgain(): void
    {
        $room = new Rooms();
        $room->setTimeZone('Europe/Berlin');
        $start = new \DateTime('2026-01-15T10:00:00', new \DateTimeZone('Europe/Berlin'));
        $room->setStart($start);

        $room->setStart(null);
        self::assertNull($room->getStartUtc());

        $newStart = new \DateTime('2026-06-15T14:00:00', new \DateTimeZone('Europe/Berlin'));
        $room->setStart($newStart);
        self::assertNotNull($room->getStartUtc());
        self::assertEquals('2026-06-15 12:00:00', $room->getStartUtc()->format('Y-m-d H:i:s'));
    }

    public function testSetUTCTimeHandlesDifferentTimezones(): void
    {
        $room = new Rooms();
        $room->setTimeZone('America/New_York');
        $start = new \DateTime('2026-12-25T09:00:00', new \DateTimeZone('America/New_York'));
        $room->setStart($start);

        self::assertEquals('2026-12-25 14:00:00', $room->getStartUtc()->format('Y-m-d H:i:s'));

        $room->setStart(null);

        self::assertNull($room->getStartUtc());
        self::assertNull($room->getStartTimestamp());
    }
}
