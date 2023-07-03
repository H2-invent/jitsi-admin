<?php

namespace App\Tests\Rooms\Service;

use App\Entity\CallerId;
use App\Entity\CallerSession;
use App\Entity\LobbyWaitungUser;
use App\Entity\Repeat;
use App\Entity\Subscriber;
use App\Entity\Waitinglist;
use App\Repository\RoomsRepository;
use App\Service\caller\CallerPrepareService;
use App\Service\RemoveRoomService;
use App\Service\RepeaterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RemoveRoomTest extends KernelTestCase
{
    public function testRemoveAll(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $removeRoomService = self::getContainer()->get(RemoveRoomService::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = $room->getModerator();
        $room->addFavoriteUser($user);
        $lobbyWatingUSer = new LobbyWaitungUser();
        $lobbyWatingUSer->setUser($user);
        $lobbyWatingUSer->setRoom($room);
        $lobbyWatingUSer->setType('a');
        $lobbyWatingUSer->setCreatedAt(new \DateTime());
        $lobbyWatingUSer->setShowName('test');
        $lobbyWatingUSer->setUid('test');
        $modSId = null;
        foreach ($room->getUser() as $data) {
            $callerID = new CallerId();
            $callerID->setRoom($room);
            $callerID->setCreatedAt(new \DateTime());
            $callerID->setUser($data);
            $callerID->setCallerId('test123');
            if ($data == $room->getModerator()) {
                $modSId = $callerID;
            }
            $room->addCallerId($callerID);
        }
        $callerSession = new CallerSession();
        $callerSession->setAuthOk(false);
        $callerSession->setCallerId('1234');
        $callerSession->setCaller($modSId);
        $callerSession->setCreatedAt(new \DateTime());
        $callerSession->setSessionId('test');
        $callerSession->setShowName('test');
        $callerSession->setLobbyWaitingUser($lobbyWatingUSer);
        $lobbyWatingUSer->setCallerSession($callerSession);
        $em->persist($lobbyWatingUSer);
        $em->flush();
        $room->addLobbyWaitungUser($lobbyWatingUSer);
        $sub = new Subscriber();
        $sub->setUser($user);
        $sub->setUid('kjdshfkhdsj');
        $sub->setRoom($room);
        $em->persist($sub);
        $em->flush();


        $wait = new Waitinglist();
        $wait->setUser($user);
        $wait->setCreatedAt(new \DateTime());
        $wait->setRoom($room);
        $em->persist($wait);
        $em->flush();

        $callerPrepareService->createUserCallerIDforRoom($room);
        $callerPrepareService->addCallerIdToRoom($room);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, sizeof($room->getCallerIds()));
        self::assertNotNull($room->getCallerRoom()->getId());

        self::assertEquals(1, sizeof($room->getLobbyWaitungUsers()));
        self::assertEquals(1, sizeof($room->getSubscribers()));
        self::assertEquals(1, sizeof($room->getWaitinglists()));

        self::assertEquals(true, $removeRoomService->deleteRoom($room));

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(0, sizeof($room->getUser()));
        self::assertNull($room->getModerator());
        self::assertEquals(0, sizeof($room->getFavoriteUsers()));
        self::assertEquals(0, sizeof($room->getLobbyWaitungUsers()));
        self::assertEquals(0, sizeof($room->getSubscribers()));
        self::assertEquals(0, sizeof($room->getWaitinglists()));
        self::assertEquals(0, sizeof($room->getCallerIds()));
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertNull($room->getCallerRoom()->getId());
    }

    public function testRemovefromRepeat(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $removeRoomService = self::getContainer()->get(RemoveRoomService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
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
