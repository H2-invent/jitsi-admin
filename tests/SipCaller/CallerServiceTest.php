<?php

namespace App\Tests\SipCaller;

use App\Repository\CallerSessionRepository;
use App\Repository\LobbyWaitungUserRepository;
use App\Repository\RoomsRepository;
use App\Service\caller\CallerFindRoomService;
use App\Service\caller\CallerPinService;
use App\Service\caller\CallerPrepareService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CallerServiceTest extends KernelTestCase
{
    public function testGetroomSuccess(): void
    {
        $kernel = self::bootKernel();
        $callerService = self::getContainer()->get(CallerFindRoomService::class);
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $id = '12340';
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);

        self::assertEquals(['status' => 'ACCEPTED', 'startTime' => $room->getStartTimestamp(), 'endTime' => $room->getEndTimestamp(), 'roomName' => $room->getName(), 'links' => ['pin' => $urlGen->generate('caller_pin', ['roomId' => $id])]], $callerService->findRoom($id));
    }
    public function testGetPersistantRoomSuccess(): void
    {
        $kernel = self::bootKernel();
        $callerService = self::getContainer()->get(CallerFindRoomService::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);

        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'This is a fixed room']);
        $callerPrepareService->addCallerIdToRoom($room);
        $room = $roomRepo->findOneBy(['name' => 'This is a fixed room']);
        $id = $room->getCallerRoom()->getCallerId();
        self::assertEquals(['status' => 'ACCEPTED', 'startTime' => $room->getStartTimestamp(), 'endTime' => $room->getEndTimestamp(), 'roomName' => $room->getName(), 'links' => ['pin' => $urlGen->generate('caller_pin', ['roomId' => $id])]], $callerService->findRoom($id));
    }
    public function testGetrromToEarly(): void
    {
        $kernel = self::bootKernel();
        $callerService = self::getContainer()->get(CallerFindRoomService::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $id = '123419';
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        self::assertEquals(['status' => 'HANGUP', 'reason' => 'TO_EARLY', 'startTime' => $room->getStartTimestamp(), 'endTime' => $room->getEndTimestamp(), 'links' => []], $callerService->findRoom($id));
    }
    public function testGetrromToLate(): void
    {
        $kernel = self::bootKernel();
        $callerService = self::getContainer()->get(CallerFindRoomService::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $id = '123456';
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'Room Yesterday']);
        self::assertEquals(['status' => 'HANGUP', 'reason' => 'TO_LATE', 'startTime' => $room->getStartTimestamp(), 'endTime' => $room->getEndTimestamp(), 'links' => []], $callerService->findRoom($id));
    }
    public function testGetrromUnknown(): void
    {
        $kernel = self::bootKernel();
        $callerService = self::getContainer()->get(CallerFindRoomService::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $id = 'unknownId';
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'Room Yesterday']);
        self::assertEquals(['status' => 'ROOM_ID_UKNOWN', 'reason' => 'ROOM_ID_UKNOWN', 'links' => []], $callerService->findRoom($id));
    }
    public function testGetPinRoomUnknown(): void
    {
        $kernel = self::bootKernel();
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $id = 'unknownId';
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'Room Yesterday']);
        self::assertEquals(null, $callerPinService->createNewCallerSession($id, '0000', '012345'));
    }
    public function testGetPinRoomCorrectPinWrong(): void
    {
        $kernel = self::bootKernel();
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $id = '123419';
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        self::assertEquals(null, $callerPinService->createNewCallerSession($id, '0000', '012345'));
    }
    public function testGetPinRoomCorrectPinCorrect(): void
    {
        $kernel = self::bootKernel();
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $id = '123419';
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $caller = $room->getCallerIds()[0];
        $lobbyUSerRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $lobbyWaitingUser = $lobbyUSerRepo->findOneBy(['room' => $room, 'user' => $caller->getUser()]);
        $sessionRepo = self::getContainer()->get(CallerSessionRepository::class);
        $session = $sessionRepo->findOneBy(['lobbyWaitingUser' => $lobbyWaitingUser]);
        self::assertNull($session);
        self::assertNull($lobbyWaitingUser);
        self::assertNotNull($callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345'));
        $lobbyWaitingUser = $lobbyUSerRepo->findOneBy(['room' => $room, 'user' => $caller->getUser()]);
        $session = $sessionRepo->findOneBy(['lobbyWaitingUser' => $lobbyWaitingUser]);

        self::assertEquals(1, sizeof($room->getLobbyWaitungUsers()));
        self::assertEquals($lobbyWaitingUser, $session->getLobbyWaitingUser());
        self::assertFalse($session->getAuthOk());
        self::assertFalse($session->isIsSipVideoUser());
        self::assertNotNull($session);
        self::assertNotNull($lobbyWaitingUser);
        self::assertEquals($lobbyWaitingUser->getShowName(), $session->getShowName());
        self::assertEquals('c', $lobbyWaitingUser->getType());
        self::assertEquals('User, Test, test@local.de', $lobbyWaitingUser->getShowName());
        self::assertEquals(1, sizeof($room->getLobbyWaitungUsers()));
        self::assertEquals(null, $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345'));
    }

    public function testGetPinRoomCorrectPinCorrectSetSipVideoTrue(): void
    {
        $kernel = self::bootKernel();
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $id = '123419';
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $caller = $room->getCallerIds()[0];
        $lobbyUSerRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $lobbyWaitingUser = $lobbyUSerRepo->findOneBy(['room' => $room, 'user' => $caller->getUser()]);
        $sessionRepo = self::getContainer()->get(CallerSessionRepository::class);
        $session = $sessionRepo->findOneBy(['lobbyWaitingUser' => $lobbyWaitingUser]);
        self::assertNull($session);
        self::assertNull($lobbyWaitingUser);
        self::assertNotNull($callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345',true));
        $lobbyWaitingUser = $lobbyUSerRepo->findOneBy(['room' => $room, 'user' => $caller->getUser()]);
        $session = $sessionRepo->findOneBy(['lobbyWaitingUser' => $lobbyWaitingUser]);

        self::assertEquals(1, sizeof($room->getLobbyWaitungUsers()));
        self::assertEquals($lobbyWaitingUser, $session->getLobbyWaitingUser());
        self::assertFalse($session->getAuthOk());
        self::assertTrue($session->isIsSipVideoUser());
        self::assertNotNull($session);
        self::assertNotNull($lobbyWaitingUser);
        self::assertEquals($lobbyWaitingUser->getShowName(), $session->getShowName());
        self::assertEquals('c', $lobbyWaitingUser->getType());
        self::assertEquals('User, Test, test@local.de', $lobbyWaitingUser->getShowName());
        self::assertEquals(1, sizeof($room->getLobbyWaitungUsers()));
        self::assertEquals(null, $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345'));
    }
}
