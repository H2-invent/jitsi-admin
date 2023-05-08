<?php

namespace App\Tests\SipCaller;

use App\Entity\CallerSession;
use App\Entity\RoomStatus;
use App\Entity\RoomStatusParticipant;
use App\Repository\CallerSessionRepository;
use App\Repository\LobbyWaitungUserRepository;
use App\Repository\PredefinedLobbyMessagesRepository;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\caller\CallerPinService;
use App\Service\caller\CallerPrepareService;
use App\Service\caller\CallerSessionService;
use App\Service\Lobby\DirectSendService;
use App\Service\Lobby\SendMessageToWaitingUser;
use App\Service\RoomService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use function PHPUnit\Framework\assertEquals;

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
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345');
        // vorbereitung
        self::assertEquals(['status' => 'HANGUP', 'reason' => 'WRONG_SESSION'], $sessionService->getSessionStatus('12345'));
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
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $room->setLobby(true);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345');
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        // vorbereitung
        self::assertEquals(
            [
                'status' => 'WAITING',
                'reason' => 'NOT_ACCEPTED',
                'number_of_participants' => 0,
                'status_of_meeting' => 'NOT_STARTED',
                "message" => [],
                'links' => [
                    'session' => $urlGen->generate('caller_session', ['session_id' => $session->getSessionId()]),
                    'left' => $urlGen->generate('caller_left', ['session_id' => $session->getSessionId()]),
                ]
            ],
            $sessionService->getSessionStatus($session->getSessionId())
        );
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
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $room->setLobby(true);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345');
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
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        self::assertEquals(
            [
                'status' => 'WAITING',
                'reason' => 'NOT_ACCEPTED',
                'number_of_participants' => 0,
                'status_of_meeting' => 'STARTED',
                "message" => [],
                'links' => [
                    'session' => $urlGen->generate('caller_session', ['session_id' => $session->getSessionId()]),
                    'left' => $urlGen->generate('caller_left', ['session_id' => $session->getSessionId()]),
                ]
            ],
            $sessionService->getSessionStatus($session->getSessionId())
        );
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
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $room->setLobby(true);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345');
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
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        self::assertEquals(
            [
                'status' => 'WAITING',
                'reason' => 'NOT_ACCEPTED',
                'number_of_participants' => 1,
                'status_of_meeting' => 'STARTED',
                "message" => [],
                'links' => [
                    'session' => $urlGen->generate('caller_session', ['session_id' => $session->getSessionId()]),
                    'left' => $urlGen->generate('caller_left', ['session_id' => $session->getSessionId()]),
                ]
            ],
            $sessionService->getSessionStatus($session->getSessionId())
        );
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
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $room->setLobby(true);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345');
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
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        self::assertEquals(
            [
                'status' => 'HANGUP',
                'reason' => 'MEETING_HAS_FINISHED',
                "message" => [],
                'links' => [
                    'left' => $urlGen->generate('caller_left', ['session_id' => $session->getSessionId()]),
                ]
            ],
            $sessionService->getSessionStatus($session->getSessionId())
        );
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        self::assertEquals(
            [
                'status' => 'HANGUP',
                'reason' => 'WRONG_SESSION',

            ],
            $sessionService->getSessionStatus($session->getSessionId())
        );
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
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $room->setLobby(true);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345');
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
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        self::assertEquals(
            [
                'status' => 'WAITING',
                'reason' => 'NOT_ACCEPTED',
                'number_of_participants' => 0,
                'status_of_meeting' => 'STARTED',
                "message" => [],
                'links' => [
                    'session' => $urlGen->generate('caller_session', ['session_id' => $session->getSessionId()]),
                    'left' => $urlGen->generate('caller_left', ['session_id' => $session->getSessionId()]),
                ]
            ],
            $sessionService->getSessionStatus($session->getSessionId())
        );
        $status->setDestroyedAt(new \DateTime())
            ->setDestroyed(true);
        $manager->persist($status);
        $manager->flush();
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        self::assertEquals(
            [
                'status' => 'HANGUP',
                'reason' => 'MEETING_HAS_FINISHED',
                "message" => [],
                'links' => [
                    'left' => $urlGen->generate('caller_left', ['session_id' => $session->getSessionId()]),
                ]
            ],
            $sessionService->getSessionStatus($session->getSessionId())
        );
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        self::assertEquals(
            [
                'status' => 'HANGUP',
                'reason' => 'WRONG_SESSION',
            ],
            $sessionService->getSessionStatus($session->getSessionId())
        );
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
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $room->setLobby(true);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345');
        $manager->remove($session->getLobbyWaitingUser());
        $session->setLobbyWaitingUser(null);
        $manager->persist($session);
        $manager->flush();
        // vorbereitung
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        self::assertEquals(
            [
                'status' => 'HANGUP',
                'reason' => 'DECLINED',
                "message" => [],
                'links' => []
            ],
            $sessionService->getSessionStatus($session->getSessionId())
        );
        self::assertEquals(
            [
                'status' => 'HANGUP',
                'reason' => 'WRONG_SESSION'
            ],
            $sessionService->getSessionStatus($session->getSessionId())
        );
    }

    public function testWaitingAccepted(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $roomService = self::getContainer()->get(RoomService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345');

        // vorbereitung
        $session->setAuthOk(true);
        $manager->persist($session);
        $manager->flush();
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        self::assertEquals(
            [
                'status' => 'ACCEPTED',
                'reason' => 'ACCEPTED_BY_MODERATOR',
                'number_of_participants' => 0,
                'status_of_meeting' => 'STARTED',
                "message" => [],
                'room_name' => $room->getUid(),
                'displayname' => 'User, Test, test@local.de',
                'jwt' => $roomService->generateJwt($session->getCaller()->getRoom(), $session->getCaller()->getUser(), $session->getShowName()),
                'links' => [
                    'session' => $urlGen->generate('caller_session', ['session_id' => $session->getSessionId()]),
                    'left' => $urlGen->generate('caller_left', ['session_id' => $session->getSessionId()]),
                ]
            ],
            $sessionService->getSessionStatus($session->getSessionId())
        );
    }

    public function testAccept(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $roomService = self::getContainer()->get(RoomService::class);

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345');
        $lobbyWaitinguserRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $lobbywaitinguser = $lobbyWaitinguserRepo->findOneBy(['room' => $room, 'user' => $session->getCaller()->getUser()]);
        self::assertTrue($sessionService->acceptCallerUser($lobbywaitinguser));
        self::assertTrue($session->getAuthOk());
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        self::assertEquals(
            [
                'status' => 'ACCEPTED',
                'reason' => 'ACCEPTED_BY_MODERATOR',
                'number_of_participants' => 0,
                'status_of_meeting' => 'STARTED',
                'room_name' => $room->getUid(),
                "message" => [],
                'displayname' => 'User, Test, test@local.de',
                'jwt' => $roomService->generateJwt($session->getCaller()->getRoom(), $session->getCaller()->getUser(), $session->getShowName()),
                'links' => [
                    'session' => $urlGen->generate('caller_session', ['session_id' => $session->getSessionId()]),
                    'left' => $urlGen->generate('caller_left', ['session_id' => $session->getSessionId()]),
                ]
            ],
            $sessionService->getSessionStatus($session->getSessionId())
        );
    }

    public function testVerifyFalse(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $roomService = self::getContainer()->get(RoomService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345');
        self::assertFalse($callerPinService->verifyCallerID($session));
    }

    public function testVerifyTrue(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $roomService = self::getContainer()->get(RoomService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '0123456789');
        self::assertTrue($callerPinService->verifyCallerID($session));
    }

    public function testVerifyFail(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $roomService = self::getContainer()->get(RoomService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $caller->getUser()->setSpezialProperties([]);
        $session = $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '0123456789');
        self::assertFalse($callerPinService->verifyCallerID($session));
    }

    public function testVerifyTrueWithClean(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $roomService = self::getContainer()->get(RoomService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        self::assertEquals('1234', $callerPinService->clean('12/34'));
        self::assertEquals('1234', $callerPinService->clean('12 34'));
        self::assertEquals('1234', $callerPinService->clean('12a34'));
        self::assertEquals('1234', $callerPinService->clean('1#2 3/4'));
        $session = $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '0123-456/7sdf89');
        self::assertTrue($callerPinService->verifyCallerID($session));
    }

    public function testWaitingFinishedForAll(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $room->setLobby(true);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345');
        $session->setAuthOk(true);
        $session->setForceFinish(true);
        $manager->persist($session);
        $manager->flush();
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        self::assertEquals(
            [
                'status' => 'HANGUP',
                'reason' => 'MEETING_HAS_FINISHED',
                "message" => [],
                'links' => [
                    'left' => $urlGen->generate('caller_left', ['session_id' => $session->getSessionId()]),
                ]
            ],
            $sessionService->getSessionStatus($session->getSessionId())
        );


        $session = $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345');
        $session->setLobbyWaitingUser(null);
        $session->setAuthOk(true);
        $session->setForceFinish(true);
        $manager->persist($session);
        $manager->flush();
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        self::assertEquals(
            [
                'status' => 'HANGUP',
                'reason' => 'MEETING_HAS_FINISHED',
                "message" => [],
                'links' => [
                    'left' => $urlGen->generate('caller_left', ['session_id' => $session->getSessionId()]),
                ]
            ],
            $sessionService->getSessionStatus($session->getSessionId())
        );
    }


    public function testWaitingNotStartedLobbyDeactivated(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $room->setLobby(false);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345');
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        // vorbereitung
        self::assertEquals(
            [
                'status' => 'WAITING',
                'reason' => 'NOT_ACCEPTED',
                'number_of_participants' => 0,
                'status_of_meeting' => 'NOT_STARTED',
                "message" => [],
                'links' => [
                    'session' => $urlGen->generate('caller_session', ['session_id' => $session->getSessionId()]),
                    'left' => $urlGen->generate('caller_left', ['session_id' => $session->getSessionId()]),
                ]
            ],
            $sessionService->getSessionStatus($session->getSessionId())
        );
    }


    public function testLobbydeactivated(): void
    {
        $kernel = self::bootKernel();


        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $roomService = self::getContainer()->get(RoomService::class);

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $room->setLobby(false);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345');
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $status = new RoomStatus();
        $status->setRoom($room)
            ->setCreatedAt(new \DateTime())
            ->setJitsiRoomId('test')
            ->setCreated(true)
            ->setRoomCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());
        $manager->persist($status);
        $manager->flush();
        self::assertEquals(
            [
                'status' => 'ACCEPTED',
                'reason' => 'ACCEPTED_BY_MODERATOR',
                'number_of_participants' => 0,
                'status_of_meeting' => 'STARTED',
                "message" => [],
                'displayname' => 'User, Test, test@local.de',
                'jwt' => $roomService->generateJwt($session->getCaller()->getRoom(), $session->getCaller()->getUser(), $session->getShowName()),
                'links' => [
                    'session' => $urlGen->generate('caller_session', ['session_id' => $session->getSessionId()]),
                    'left' => $urlGen->generate('caller_left', ['session_id' => $session->getSessionId()]),
                ],
                'room_name' => $session->getCaller()->getRoom()->getUid()
            ],
            $sessionService->getSessionStatus($session->getSessionId())
        );
    }

    public function testCreateResponseMessage(): void
    {
        $kernel = self::bootKernel();

        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerSession = new CallerSession();
        $callerSession->setMessageUid('testUID')->setMessageText('Test Message');
        assertEquals(['uid' => 'testUID', 'message' => 'Test Message'], $sessionService->createMessageElement($callerSession));
    }
    public function testCreateResponseMessageEmpty(): void
    {
        $kernel = self::bootKernel();

        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerSession = new CallerSession();
        $callerSession->setMessageUid(null)->setMessageText(null);
        assertEquals([], $sessionService->createMessageElement($callerSession));
    }

    public function testSendMessageToCallerIn(): void
    {
        $kernel = self::bootKernel();
        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                self::assertEquals('{"type":"message","message":"test Nachricht","from":"Test1, 1234, User, Test"}', $update->getData());
                self::assertEquals(['lobby_WaitingUser_websocket/c4ca4238a0b923820dcc509a6f75849b'], $update->getTopics());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $message = $messageRepo->findAll();
        $sendMessage = self::getContainer()->get(SendMessageToWaitingUser::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $waitingUSerRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $waitingUser = $waitingUSerRepo->findOneBy(['uid' => md5(1)]);
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerSession = new CallerSession();
        $callerSession->setSessionId('test')
            ->setAuthOk(false)
            ->setCreatedAt(new \DateTime())
            ->setShowName('testUser');
        $waitingUser->setCallerSession($callerSession);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($callerSession);
        $em->persist($waitingUser);
        $em->flush();

        self::assertEquals(true, $sendMessage->sendMessage(md5(1), 'test Nachricht', $user));

        $callerSessionRepo = self::getContainer()->get(CallerSessionRepository::class);
        $callerSession2 = $callerSessionRepo->findOneBy(['sessionId' => 'test']);
        assertEquals(['uid' => $callerSession2->getMessageUid(), 'message' => 'test Nachricht'], $sessionService->createMessageElement($callerSession));
    }
}
