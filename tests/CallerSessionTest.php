<?php

namespace App\Tests;

use App\Entity\CallerSession;
use App\Entity\LobbyWaitungUser;
use App\Entity\RoomStatus;
use App\Entity\RoomStatusParticipant;
use App\Repository\RoomsRepository;
use App\Service\caller\CallerPinService;
use App\Service\caller\CallerPrepareService;
use App\Service\caller\CallerSessionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CallerSessionTest extends KernelTestCase
{
    public function testNoSession(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->getPin($id, $caller->getCallerId());
        // vorbereitung
        self::assertEquals(array('status' => 'HANGUP', 'reason' => 'WRONG_SESSION'), $sessionService->getSession('12345'));
    }

    public function testWaitingNotStarted(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 19'));
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->getPin($id, $caller->getCallerId());
        // vorbereitung

        self::assertEquals(array('status' => 'WAITING', 'reason' => 'NOT_ACCEPTED', 'number_of_participants' => 0, 'status_of_meeting' => 'NOT_STARTED'), $sessionService->getSession($session->getSessionId()));
    }

    public function testWaitingStarted0Parts(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 19'));
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->getPin($id, $caller->getCallerId());
        // vorbereitung
        $status = new RoomStatus();
        $status->setRoom($room)
            ->setCreatedAt(new \DateTime())
            ->setJitsiRoomId('test')
            ->setCreated(true)
            ->setRoomCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());
        $manager->persist($status);
        $manager->flush();
        self::assertEquals(array('status' => 'WAITING', 'reason' => 'NOT_ACCEPTED', 'number_of_participants' => 0, 'status_of_meeting' => 'STARTED'), $sessionService->getSession($session->getSessionId()));
    }

    public function testWaitingStarted2Part(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 19'));
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->getPin($id, $caller->getCallerId());
        // vorbereitung
        $status = new RoomStatus();
        $status->setRoom($room)
            ->setCreatedAt(new \DateTime())
            ->setJitsiRoomId('test')
            ->setCreated(true)
            ->setRoomCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());
        $manager->persist($status);
        $manager->flush();
        $roomPart = new RoomStatusParticipant();
        $roomPart->setInRoom(true)
            ->setParticipantId('test@test.de')
            ->setEnteredRoomAt(new \DateTime())
            ->setRoomStatus($status)
            ->setParticipantName('test 1234');
        $manager->persist($roomPart);
        $manager->flush();
        self::assertEquals(array('status' => 'WAITING', 'reason' => 'NOT_ACCEPTED', 'number_of_participants' => 1, 'status_of_meeting' => 'STARTED'), $sessionService->getSession($session->getSessionId()));
    }

    public function testWaitingFinished(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 19'));
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->getPin($id, $caller->getCallerId());
        // vorbereitung
        $status = new RoomStatus();
        $status->setRoom($room)
            ->setCreatedAt(new \DateTime())
            ->setJitsiRoomId('test')
            ->setCreated(true)
            ->setRoomCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime())
            ->setDestroyedAt(new \DateTime())
            ->setDestroyed(true);
        $manager->persist($status);
        $manager->flush();
        self::assertEquals(array('status' => 'HANGUP', 'reason' => 'MEETING_HAS_FINISHED'), $sessionService->getSession($session->getSessionId()));
        self::assertEquals(array('status' => 'HANGUP', 'reason' => 'WRONG_SESSION'), $sessionService->getSession($session->getSessionId()));
    }

    public function testWaitingStartedAndThenFinished(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 19'));
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->getPin($id, $caller->getCallerId());
        // vorbereitung
        $status = new RoomStatus();
        $status->setRoom($room)
            ->setCreatedAt(new \DateTime())
            ->setJitsiRoomId('test')
            ->setCreated(true)
            ->setRoomCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());
        $manager->persist($status);
        $manager->flush();
        self::assertEquals(array('status' => 'WAITING', 'reason' => 'NOT_ACCEPTED', 'number_of_participants' => 0, 'status_of_meeting' => 'STARTED'), $sessionService->getSession($session->getSessionId()));
        $status->setDestroyedAt(new \DateTime())
            ->setDestroyed(true);
        $manager->persist($status);
        $manager->flush();
        self::assertEquals(array('status' => 'HANGUP', 'reason' => 'MEETING_HAS_FINISHED'), $sessionService->getSession($session->getSessionId()));
        self::assertEquals(array('status' => 'HANGUP', 'reason' => 'WRONG_SESSION'), $sessionService->getSession($session->getSessionId()));
    }

    public function testWaitingDeclined(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 19'));
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->getPin($id, $caller->getCallerId());
        $manager->remove($session->getLobbyWaitingUser());
        $session->setLobbyWaitingUser(null);
        $manager->persist($session);
        $manager->flush();
        // vorbereitung

        self::assertEquals(array('status' => 'HANGUP', 'reason' => 'DECLINED'), $sessionService->getSession($session->getSessionId()));
        self::assertEquals(array('status' => 'HANGUP', 'reason' => 'WRONG_SESSION'), $sessionService->getSession($session->getSessionId()));
    }

}
