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
        self::assertNull($room->getEnddate());
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
}
