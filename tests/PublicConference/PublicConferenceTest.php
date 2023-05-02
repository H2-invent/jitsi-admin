<?php

namespace App\Tests\PublicConference;

use App\Repository\RoomsRepository;
use App\Repository\ServerRepository;
use App\Service\PublicConference\PublicConferenceService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PublicConferenceTest extends KernelTestCase
{
    public function testCreateRoom(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $publicConference = self::getContainer()->get(PublicConferenceService::class);
        $serverRepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverRepo->findOneBy(['url' => 'meet.jit.si']);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $rooms = $roomRepo->findAll();
        self::assertEquals(70, sizeof($rooms));
        $room = $publicConference->createNewRoomFromName('testMyRoom', $server);
        self::assertEquals('testmyroom', $room->getName());
        self::assertEquals(md5($server->getUrl() . 'testmyroom'), $room->getUid());
        $rooms = $roomRepo->findAll();
        self::assertEquals(71, sizeof($rooms));
        $room = $publicConference->createNewRoomFromName('testMyRoom', $server);
        $rooms = $roomRepo->findAll();
        self::assertEquals(71, sizeof($rooms));
        $room = $publicConference->createNewRoomFromName('test My Room with @!"§$', $server);
        self::assertEquals('test_my_room_with_', $room->getName());
        self::assertEquals(md5($server->getUrl() . 'test_my_room_with_'), $room->getUid());
        $rooms = $roomRepo->findAll();
        self::assertEquals(72, sizeof($rooms));
        // $routerService = static::getContainer()->get('router');
        // $myCustomService = static::getContainer()->get(CustomService::class);
    }
}
