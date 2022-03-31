<?php

namespace App\Tests\Rooms;

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
        $error = array();
        $checkService->checkRoom($room,$error);
        self::assertEquals(array('Fehler, bitte das Startdatum eingeben.','Fehler, bitte den Namen angeben.'),$error);
        $room->setName('test123');
        $error = array();
        $checkService->checkRoom($room,$error);
        self::assertEquals(array('Fehler, bitte das Startdatum eingeben.'),$error);
        $room->setStart(new \DateTime());
        $room->setDuration(60);
        $error = array();
        $checkService->checkRoom($room,$error);
        self::assertEquals(array(),$error);
        $error = array();
        $room->setStart((new \DateTime())->modify('-30min'));
        $room->setDuration(60);
        $checkService->checkRoom($room,$error);
        self::assertEquals(array(),$error);
        $error = array();
        $room->setStart((new \DateTime())->modify('-70min'));
        $room->setDuration(60);
        $checkService->checkRoom($room,$error);
        self::assertEquals(array('Fehler, das Startdatum und das Enddatum liegen in der Vergangenheit.'),$error);

        $error = array();
        $room->setStart((new \DateTime()));
        $room->setDuration(60);
        $checkService->checkRoom($room,$error);
        self::assertEquals(array(),$error);
        self::assertEquals((new \DateTime())->modify('+60min')->format('H:i:s'),$room->getEnddate()->format('H:i:s'));
        self::assertStringStartsNotWith('test123-',$room->getUid());
        self::assertStringStartsNotWith('test123-',$room->getSlug());
        $error = array();
        $room->setPersistantRoom(true);
        $checkService->checkRoom($room,$error);
        self::assertEquals(array(),$error);
        self::assertNull($room->getEnddate());
        self::assertStringStartsWith('test123-',$room->getUid());
        self::assertStringStartsWith('test123-',$room->getSlug());


    }
}
