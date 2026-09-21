<?php

namespace App\Tests\SipCaller;

use App\Repository\CallerIdRepository;
use App\Repository\CallerSessionRepository;
use App\Repository\LobbyWaitungUserRepository;
use App\Repository\RoomsRepository;
use App\Service\caller\CallerFindRoomService;
use App\Service\caller\CallerPinService;
use App\Service\caller\CallerPrepareService;
use App\Service\caller\CallerSessionService;
use App\Service\Theme\ThemeService;
use Doctrine\ORM\EntityManagerInterface;
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

        self::assertEquals(['status' => 'ACCEPTED', 'startTime' => $room->getStartTimestamp(), 'endTime' => $room->getEndTimestamp(), 'roomName' => $room->getName(), 'lobby_enabled' => false, 'total_open_rooms' => false, 'pin_required' => false, 'links' => ['open' => $urlGen->generate('caller_open', ['roomId' => $id])]], $callerService->findRoom($id));
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
        self::assertEquals(['status' => 'ACCEPTED', 'startTime' => $room->getStartTimestamp(), 'endTime' => $room->getEndTimestamp(), 'roomName' => $room->getName(), 'lobby_enabled' => false, 'total_open_rooms' => false, 'pin_required' => false, 'links' => ['open' => $urlGen->generate('caller_open', ['roomId' => $id])]], $callerService->findRoom($id));
    }
    public function testGetRoomWithLobbyButWithoutPersonalPin(): void
    {
        $kernel = self::bootKernel();
        $callerService = self::getContainer()->get(CallerFindRoomService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $id = '12340';
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $lobbyBefore = $room->getLobby();

        try {
            $room->setLobby(true);
            $manager->persist($room);
            $manager->flush();

            // The test env hides personal PINs, so the protected flow cannot complete.
            self::assertEquals(
                [
                    'status' => 'HANGUP',
                    'reason' => 'NO_PIN_CONFIGURED',
                    'startTime' => $room->getStartTimestamp(),
                    'endTime' => $room->getEndTimestamp(),
                    'links' => []
                ],
                $callerService->findRoom($id)
            );
        } finally {
            // Shared fixtures require restoring the room state after each test.
            $room->setLobby($lobbyBefore);
            $manager->persist($room);
            $manager->flush();
        }
    }

    public function testGetRoomWithLobbyAndPersonalPin(): void
    {
        $kernel = self::bootKernel();
        $themeService = $this->createMock(ThemeService::class);
        $themeService->method('getApplicationProperties')->willReturn(1);
        self::getContainer()->set(ThemeService::class, $themeService);

        $callerService = self::getContainer()->get(CallerFindRoomService::class);
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $id = '12340';
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $lobbyBefore = $room->getLobby();

        try {
            $room->setLobby(true);
            $manager->persist($room);
            $manager->flush();

            self::assertEquals(
                [
                    'status' => 'ACCEPTED',
                    'startTime' => $room->getStartTimestamp(),
                    'endTime' => $room->getEndTimestamp(),
                    'roomName' => $room->getName(),
                    'lobby_enabled' => true,
                    'total_open_rooms' => false,
                    'pin_required' => true,
                    'links' => ['pin' => $urlGen->generate('caller_protected', ['roomId' => $id])]
                ],
                $callerService->findRoom($id)
            );
        } finally {
            $room->setLobby($lobbyBefore);
            $manager->persist($room);
            $manager->flush();
        }
    }

    public function testGetRoomWithLobbyAndTotalOpenRoomWithoutPersonalPin(): void
    {
        $kernel = self::bootKernel();
        $callerService = self::getContainer()->get(CallerFindRoomService::class);
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $id = '12340';
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $lobbyBefore = $room->getLobby();
        $totalOpenRoomsBefore = $room->getTotalOpenRooms();

        try {
            $room->setLobby(true);
            $room->setTotalOpenRooms(true);
            $manager->persist($room);
            $manager->flush();

            // The test env hides personal PINs, but a total open room needs no PIN and continues with the protected flow.
            self::assertEquals(
                [
                    'status' => 'ACCEPTED',
                    'startTime' => $room->getStartTimestamp(),
                    'endTime' => $room->getEndTimestamp(),
                    'roomName' => $room->getName(),
                    'lobby_enabled' => true,
                    'total_open_rooms' => true,
                    'pin_required' => false,
                    'links' => ['pin' => $urlGen->generate('caller_protected', ['roomId' => $id])]
                ],
                $callerService->findRoom($id)
            );
        } finally {
            $room->setLobby($lobbyBefore);
            $room->setTotalOpenRooms($totalOpenRoomsBefore);
            $manager->persist($room);
            $manager->flush();
        }
    }

    public function testGetrromToEarly(): void
    {
        $kernel = self::bootKernel();
        $callerService = self::getContainer()->get(CallerFindRoomService::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $id = '123419';
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $room->setStart((new \DateTime())->modify('+2 hours'));
        $room->setEnddate((new \DateTime())->modify('+4 hours'));
        $manager->persist($room);
        $manager->flush();
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

    public function testGetPinClosedRoomWithoutPin(): void
    {
        $kernel = self::bootKernel();
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);

        self::assertNull($callerPinService->createNewCallerSession('123419', null, '012345'));
        self::assertEquals(0, sizeof($room->getLobbyWaitungUsers()));
    }

    public function testGetPinTotalOpenRoomWithoutPin(): void
    {
        $kernel = self::bootKernel();
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $callerSessionService = self::getContainer()->get(CallerSessionService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $id = '123419';
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerIdRepo = self::getContainer()->get(CallerIdRepository::class);
        $lobbyUSerRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $room->setLobby(true);
        $room->setTotalOpenRooms(true);
        $manager->persist($room);
        $manager->flush();

        // without a phone number the caller can not be identified
        self::assertNull($callerPinService->createNewCallerSession($id, null, null));

        $session = $callerPinService->createNewCallerSession($id, null, '012345');
        self::assertNotNull($session);
        self::assertFalse($session->getAuthOk());
        self::assertFalse($session->getCallerIdVerified());
        self::assertEquals('012345', $session->getShowName());
        self::assertEquals('012345', $session->getCallerId());

        // the call-in user is created from the phone number and has no user
        $caller = $session->getCaller();
        self::assertNotNull($caller);
        self::assertNull($caller->getUser());
        self::assertEquals('012345', $caller->getCallerId());
        self::assertEquals($room, $caller->getRoom());

        $lobbyWaitingUser = $session->getLobbyWaitingUser();
        self::assertNotNull($lobbyWaitingUser);
        self::assertNull($lobbyWaitingUser->getUser());
        self::assertEquals('c', $lobbyWaitingUser->getType());
        self::assertEquals('012345', $lobbyWaitingUser->getShowName());
        self::assertEquals(1, sizeof($lobbyUSerRepo->findBy(['room' => $room])));

        // a second caller gets an own call-in user and an own lobby user
        $session2 = $callerPinService->createNewCallerSession($id, null, '098765');
        self::assertNotNull($session2);
        self::assertNotEquals($session->getSessionId(), $session2->getSessionId());
        self::assertEquals(2, sizeof($lobbyUSerRepo->findBy(['room' => $room])));
        self::assertEquals(2, sizeof($callerIdRepo->findBy(['room' => $room, 'user' => null])));

        // the caller waits in the lobby until the moderator accepts him
        $status = $callerSessionService->getSessionStatus($session->getSessionId());
        self::assertEquals('WAITING', $status['status']);

        // the call-in user without a user is removed together with the session
        self::assertTrue($callerSessionService->cleanUpSession($session));
        self::assertEquals(1, sizeof($callerIdRepo->findBy(['room' => $room, 'user' => null])));
        self::assertEquals(1, sizeof($lobbyUSerRepo->findBy(['room' => $room])));
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
