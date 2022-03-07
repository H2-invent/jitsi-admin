<?php

namespace App\Tests;

use App\Entity\LobbyWaitungUser;
use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Entity\Subscriber;
use App\Entity\Waitinglist;
use App\Repository\RoomsRepository;
use App\Service\RemoveRoomService;
use App\Service\RepeaterService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RemoveRoomTest extends KernelTestCase
{
    public function testRemoveAll(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $removeRoomService = self::getContainer()->get(RemoveRoomService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 0'));
        $user = $room->getModerator();
        $room->addFavoriteUser($user);
        $lobbyWatingUSer = new LobbyWaitungUser();
        $lobbyWatingUSer->setUser($user);
        $lobbyWatingUSer->setRoom($room);
        $lobbyWatingUSer->setType('a');
        $lobbyWatingUSer->setCreatedAt(new \DateTime());
        $lobbyWatingUSer->setShowName('test');
        $room->addLobbyWaitungUser($lobbyWatingUSer);
        $sub = new Subscriber();
        $sub->setUser($user);
        $sub->setUid('kjdshfkhdsj');
        $room->addSubscriber($sub);
        $wait= new Waitinglist();
        $wait->setUser($user);
        $wait->setCreatedAt(new \DateTime());
        $room->addWaitinglist($wait);
        self::assertEquals(1, sizeof($room->getLobbyWaitungUsers()));
        self::assertEquals(1, sizeof($room->getSubscribers()));
        self::assertEquals(1, sizeof($room->getWaitinglists()));
        $removeRoomService->deleteRoom($room);
        self::assertEquals(0, sizeof($room->getUser()));
        self::assertNull($room->getModerator());
        self::assertEquals(0, sizeof($room->getFavoriteUsers()));
        self::assertEquals(0, sizeof($room->getLobbyWaitungUsers()));
        self::assertEquals(0, sizeof($room->getSubscribers()));
        self::assertEquals(0, sizeof($room->getWaitinglists()));
    }
    public function testRemovefromRepeat(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $removeRoomService = self::getContainer()->get(RemoveRoomService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 0'));
        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $repeat = new Repeat();
        $repeat->setRepeatType(0);
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $repeat->setRepetation(3);
        $repeat->setRepeaterDays(1);
        $repeat = $repeaterService->createNewRepeater($repeat);

        $user = $room->getModerator();
        $room->addFavoriteUser($user);
        $room->setRepeater($repeat);
        self::assertNotNull($room->getRepeater());
        $removeRoomService->deleteRoom($room);
        self::assertEquals(0, sizeof($room->getUser()));
        self::assertNull($room->getRepeater());
    }
}
