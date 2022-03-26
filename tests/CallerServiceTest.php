<?php

namespace App\Tests;


use App\Repository\RoomsRepository;
use App\Service\caller\CallerFindRoomService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CallerServiceTest extends KernelTestCase
{
    public function testGetrromSuccess(): void
    {
        $kernel = self::bootKernel();
        $callerService = self::getContainer()->get(CallerFindRoomService::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $id = '12340';
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));

        self::assertEquals(array('status' => 'ACCEPTED', 'startTime' => $room->getStartTimestamp(), 'endTime' => $room->getEndTimestamp(), 'roomName' => $room->getName(), 'links' => array('pin' => 'url')), $callerService->findRoom($id));

    }
    public function testGetrromToEarly(): void
    {
        $kernel = self::bootKernel();
        $callerService = self::getContainer()->get(CallerFindRoomService::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $id = '123419';
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 19'));
        self::assertEquals(array('status' => 'HANGUP','reason'=>'TO_EARLY', 'startTime' => $room->getStartTimestamp(), 'endTime' => $room->getEndTimestamp(), 'links' => array()), $callerService->findRoom($id));

    }
    public function testGetrromToLate(): void
    {
        $kernel = self::bootKernel();
        $callerService = self::getContainer()->get(CallerFindRoomService::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $id = '123456';
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'Room Yesterday'));
        self::assertEquals(array('status' => 'HANGUP','reason'=>'TO_LATE', 'startTime' => $room->getStartTimestamp(), 'endTime' => $room->getEndTimestamp(), 'links' => array()), $callerService->findRoom($id));

    }
    public function testGetrromUnknown(): void
    {
        $kernel = self::bootKernel();
        $callerService = self::getContainer()->get(CallerFindRoomService::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $id = 'unknownId';
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'Room Yesterday'));
        self::assertEquals(array('status' => 'ROOM_ID_UKNOWN','reason'=>'ROOM_ID_UKNOWN', 'links' => array()), $callerService->findRoom($id));

    }
}
