<?php

namespace App\Tests\SipCaller;

use App\Entity\CallerSession;
use App\Entity\LobbyWaitungUser;
use App\Entity\RoomStatus;
use App\Entity\RoomStatusParticipant;
use App\Repository\CallerSessionRepository;
use App\Repository\LobbyWaitungUserRepository;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\caller\CallerLeftService;
use App\Service\caller\CallerPinService;
use App\Service\caller\CallerPrepareService;
use App\Service\caller\CallerSessionService;
use App\Service\RoomService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use function PHPUnit\Framework\assertFalse;

class CallerControllerTest extends WebTestCase
{
    public function testAuthorizedFalse(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/v1/lobby/sip/room/123419');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(['authorized' => false]), $client->getResponse()->getContent());

        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/123419');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(['authorized' => false]), $client->getResponse()->getContent());

        $crawler = $client->request('GET', '/api/v1/lobby/sip/session');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(['authorized' => false]), $client->getResponse()->getContent());

        $crawler = $client->request('GET', '/api/v1/lobby/sip/session/left');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(['authorized' => false]), $client->getResponse()->getContent());
    }

    public function testGetCallerRoom(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer 123456']);

        $callerLEftService = self::getContainer()->get(CallerLeftService::class);
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];


        $crawler = $client->request('GET', '/api/v1/lobby/sip/room/123419');
        $this->assertResponseIsSuccessful();

        $this->assertJsonStringEqualsJsonString(json_encode(['status' => 'HANGUP', 'reason' => 'TO_EARLY', 'endTime' => $room->getEndTimestamp(), 'startTime' => $room->getStartTimestamp(), 'links' => []]), $client->getResponse()->getContent());
        $crawler = $client->request('GET', '/api/v1/lobby/sip/room/1234190');
        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString(json_encode(['status' => 'ROOM_ID_UKNOWN', 'reason' => 'ROOM_ID_UKNOWN', 'links' => []]), $client->getResponse()->getContent());
    }

    public function testGetCallerPin(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer 123456']);

        $callerLEftService = self::getContainer()->get(CallerLeftService::class);
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];


        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/' . $id, []);
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(['error' => 'MISSING_ARGUMENT', 'argument' => ['pin', 'caller_id']]), $client->getResponse()->getContent());

        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/' . $id, ['pin' => '1234']);
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(['error' => 'MISSING_ARGUMENT', 'argument' => ['caller_id']]), $client->getResponse()->getContent());

        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/' . $id, ['pin' => '1234', 'caller_id' => '1234']);
        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString(
            json_encode(
                [
                    'auth_ok' => false,
                    'links' => []
                ]
            ),
            $client->getResponse()->getContent()
        );
        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/12' . $id, ['pin' => '1234', 'caller_id' => '1234']);
        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString(
            json_encode(
                [
                    'auth_ok' => false,
                    'links' => []
                ]
            ),
            $client->getResponse()->getContent()
        );

        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/' . $id, ['pin' => $caller->getCallerId(), 'caller_id' => '1234']);
        $this->assertResponseIsSuccessful();
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $caller = $room->getCallerIds()[0];
        $session = $caller->getCallerSession();
        assertFalse($session->isIsSipVideoUser());
        $this->assertJsonStringEqualsJsonString(
            json_encode(
                [
                    'auth_ok' => true,
                    'links' => [
                        'session' => '/api/v1/lobby/sip/session?session_id=' . $session->getSessionId(),
                        'left' => '/api/v1/lobby/sip/session/left?session_id=' . $session->getSessionId()
                    ]
                ]
            ),
            $client->getResponse()->getContent()
        );
    }

    public function testGetCallerSession(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer 123456']);

        $callerLEftService = self::getContainer()->get(CallerLeftService::class);
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];

        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/' . $id, ['pin' => $caller->getCallerId(), 'caller_id' => '1234']);
        $this->assertResponseIsSuccessful();
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $caller = $room->getCallerIds()[0];
        $session = $caller->getCallerSession();

        $crawler = $client->request('GET', '/api/v1/lobby/sip/session');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(['error' => 'MISSING_ARGUMENT', 'argument' => ['session_id']]), $client->getResponse()->getContent());

        $crawler = $client->request('GET', '/api/v1/lobby/sip/session', ['session_id' => $caller->getCallerId()]);
        $this->assertResponseIsSuccessful();
    }

    public function testGetCallerLeft(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer 123456']);

        $callerLEftService = self::getContainer()->get(CallerLeftService::class);
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];

        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/' . $id, ['pin' => $caller->getCallerId(), 'caller_id' => '1234']);
        $this->assertResponseIsSuccessful();
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $caller = $room->getCallerIds()[0];
        $session = $caller->getCallerSession();

        $crawler = $client->request('GET', '/api/v1/lobby/sip/session');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(['error' => 'MISSING_ARGUMENT', 'argument' => ['session_id']]), $client->getResponse()->getContent());

        $crawler = $client->request('GET', '/api/v1/lobby/sip/session/left', ['session_id' => $caller->getCallerId()]);
        $this->assertResponseIsSuccessful();
    }


    /**
     * @return int|string
     */
    public function testFinishMeeing(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer 123456']);

        $res = $this->startWorkflow($client);
        $sessionLink = $res[0];
        $leafLink = $res[1];
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $room->setLobby(true);
        $status = new RoomStatus();
        $status->setRoom($room)
            ->setCreatedAt(new \DateTime())
            ->setJitsiRoomId('test')
            ->setCreated(true)
            ->setRoomCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());
        $manager->persist($status);
        $manager->flush();


        $crawler = $client->request('GET', $sessionLink);

        self::assertEquals(
            [
                'status' => "WAITING",
                "reason" => "NOT_ACCEPTED",
                "number_of_participants" => 0,
                "status_of_meeting" => "STARTED",
                "message" => [],
                'links' => [
                    'session' => $sessionLink,
                    'left' => $leafLink,
                ]
            ],
            json_decode($client->getResponse()->getContent(), true)
        );

        $this->assertResponseIsSuccessful();

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $status = $room->getRoomstatuses()[0];
        $roomPart = new RoomStatusParticipant();
        $roomPart->setParticipantName('test12')
            ->setEnteredRoomAt(new \DateTime())
            ->setRoomStatus($status)
            ->setInRoom(true)
            ->setParticipantId('1234');
        $manager->persist($roomPart);
        $manager->flush();

        $crawler = $client->request('GET', $sessionLink);
        self::assertEquals(
            [
                'status' => "WAITING",
                "reason" => "NOT_ACCEPTED",
                "number_of_participants" => 1,
                "status_of_meeting" => "STARTED",
                "message" => [],
                'links' => [
                    'session' => $sessionLink,
                    'left' => $leafLink,
                ]
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
        $this->assertResponseIsSuccessful();

        $roomPart = new RoomStatusParticipant();
        $roomPart->setParticipantName('test122')
            ->setEnteredRoomAt(new \DateTime())
            ->setRoomStatus($status)
            ->setInRoom(true)
            ->setParticipantId('12345');
        $manager->persist($roomPart);
        $manager->flush();
        $crawler = $client->request('GET', $sessionLink);
        self::assertEquals(
            [
                'status' => "WAITING",
                "reason" => "NOT_ACCEPTED",
                "number_of_participants" => 2,
                "status_of_meeting" => "STARTED",
                "message" => [],
                'links' => [
                    'session' => $sessionLink,
                    'left' => $leafLink,
                ]
            ],
            json_decode($client->getResponse()->getContent(), true)
        );

        $this->assertResponseIsSuccessful();

        $status->setDestroyedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime())
            ->setCreated(false)
            ->setDestroyed(true);
        $manager->persist($status);
        $manager->flush();
        $crawler = $client->request('GET', $sessionLink);
        self::assertEquals(
            [
                'status' => "HANGUP",
                "reason" => "MEETING_HAS_FINISHED",
                "message" => [],
                'links' => [
                    'left' => $leafLink,
                ]
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
        $this->assertResponseIsSuccessful();
    }


    public function testDeclineCaller(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer 123456']);
        $res = $this->startWorkflow($client);
        $sessionLink = $res[0];
        $leafLink = $res[1];

        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $moderator = $userRepo->findOneBy(['email' => 'test@local.de']);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $room->setLobby(true);
        $status = new RoomStatus();
        $status->setRoom($room)
            ->setCreatedAt(new \DateTime())
            ->setJitsiRoomId('test')
            ->setCreated(true)
            ->setRoomCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());
        $manager->persist($status);
        $manager->flush();


        $crawler = $client->request('GET', $sessionLink);
        self::assertEquals(
            [
                'status' => "WAITING",
                "reason" => "NOT_ACCEPTED",
                "number_of_participants" => 0,
                "status_of_meeting" => "STARTED",
                "message" => [],
                'links' => [
                    'session' => $sessionLink,
                    'left' => $leafLink,
                ]
            ],
            json_decode($client->getResponse()->getContent(), true)
        );

        $this->assertResponseIsSuccessful();

        $client->loginUser($moderator);
        $session = $this->getLobbyWaitinguser($sessionLink);;
        $crawler = $client->request('GET', '/room/lobby/decline/' . $session->getUid());
        $crawler = $client->request('GET', $sessionLink);
        self::assertEquals(
            [
                'status' => "HANGUP",
                "reason" => "DECLINED",
                "message" => [],
                'links' => []
            ],
            json_decode($client->getResponse()->getContent(), true)
        );

        $this->assertResponseIsSuccessful();
    }

    public function testAcceptCaller(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer 123456']);
        $res = $this->startWorkflow($client);
        $sessionLink = $res[0];
        $leafLink = $res[1];
        $roomService = self::getContainer()->get(RoomService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $moderator = $userRepo->findOneBy(['email' => 'test@local.de']);

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $room->setLobby(true);
        $status = new RoomStatus();
        $status->setRoom($room)
            ->setCreatedAt(new \DateTime())
            ->setJitsiRoomId('test')
            ->setCreated(true)
            ->setRoomCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());
        $manager->persist($status);
        $manager->flush();


        $crawler = $client->request('GET', $sessionLink);
        self::assertEquals(
            [
                'status' => "WAITING",
                "reason" => "NOT_ACCEPTED",
                "number_of_participants" => 0,
                "status_of_meeting" => "STARTED",
                "message" => [],
                'links' => [
                    'session' => $sessionLink,
                    'left' => $leafLink,
                ]
            ],
            json_decode($client->getResponse()->getContent(), true)
        );

        $this->assertResponseIsSuccessful();

        $client->loginUser($moderator);
        $lobbyWaitinguser = $this->getLobbyWaitinguser($sessionLink);
        $crawler = $client->request('GET', '/room/lobby/accept/' . $lobbyWaitinguser->getUid());
        $crawler = $client->request('GET', $sessionLink);
        $session = $this->getSessionfromLink($sessionLink);
        self::assertEquals(
            [
                'status' => 'ACCEPTED',
                'reason' => 'ACCEPTED_BY_MODERATOR',
                "message" => [],
                'number_of_participants' => 0,
                'status_of_meeting' => 'STARTED',
                'room_name' => $room->getUid(),
                'displayname' => 'User2, Test2, test@local2.de',
                'jwt' => $roomService->generateJwt($session->getCaller()->getRoom(), $session->getCaller()->getUser(), $session->getShowName()),
                'links' => [
                    'session' => $sessionLink,
                    'left' => $leafLink,
                ]
            ],
            json_decode($client->getResponse()->getContent(), true)
        );

        $this->assertResponseIsSuccessful();
    }

    public function testAcceptAllCaller(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer 123456']);
        $res = $this->startWorkflow($client);
        $sessionLink = $res[0];
        $leafLink = $res[1];
        $roomService = self::getContainer()->get(RoomService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $moderator = $userRepo->findOneBy(['email' => 'test@local.de']);

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $room->setLobby(true);
        $status = new RoomStatus();
        $status->setRoom($room)
            ->setCreatedAt(new \DateTime())
            ->setJitsiRoomId('test')
            ->setCreated(true)
            ->setRoomCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());
        $manager->persist($status);
        $manager->flush();


        $crawler = $client->request('GET', $sessionLink);
        self::assertEquals(
            [
                'status' => 'WAITING',
                'reason' => 'NOT_ACCEPTED',
                'number_of_participants' => 0,
                'status_of_meeting' => 'STARTED',
                "message" => [],
                'links' => [
                    'session' => $sessionLink,
                    'left' => $leafLink,
                ]
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
        $this->assertResponseIsSuccessful();

        $client->loginUser($moderator);
        $lobbyWaitinguser = $this->getLobbyWaitinguser($sessionLink);
        $crawler = $client->request('GET', '/room/lobby/acceptAll/' . $lobbyWaitinguser->getRoom()->getUidReal());
        $crawler = $client->request('GET', $sessionLink);
        $session = $this->getSessionfromLink($sessionLink);
        self::assertEquals(
            [
                'status' => 'ACCEPTED',
                'reason' => 'ACCEPTED_BY_MODERATOR',
                'number_of_participants' => 0,
                'status_of_meeting' => 'STARTED',
                'room_name' => $room->getUid(),
                "message" => [],
                'displayname' => 'User2, Test2, test@local2.de',
                'jwt' => $roomService->generateJwt($session->getCaller()->getRoom(), $session->getCaller()->getUser(), $session->getShowName()),
                'links' => [
                    'session' => $sessionLink,
                    'left' => $leafLink,
                ]
            ],
            json_decode($client->getResponse()->getContent(), true)
        );

        $this->assertResponseIsSuccessful();
    }

    public function testCallerLeft(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer 123456']);
        $links = $this->startWorkflow($client);
        $sessionLink = $links[0];
        $leafLink = $links[1];
        $roomService = self::getContainer()->get(RoomService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);


        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $room->setLobby(true);
        $status = new RoomStatus();
        $status->setRoom($room)
            ->setCreatedAt(new \DateTime())
            ->setJitsiRoomId('test')
            ->setCreated(true)
            ->setRoomCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());
        $manager->persist($status);
        $manager->flush();


        $crawler = $client->request('GET', $sessionLink);
        self::assertEquals(
            [
                'status' => 'WAITING',
                'reason' => 'NOT_ACCEPTED',
                'number_of_participants' => 0,
                'status_of_meeting' => 'STARTED',
                "message" => [],
                'links' => [
                    'session' => $sessionLink,
                    'left' => $leafLink,
                ]
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
        $this->assertResponseIsSuccessful();

        $crawler = $client->request('GET', $leafLink);
        $this->assertJsonStringEqualsJsonString(
            json_encode(
                [
                    'error' => false,
                ]
            ),
            $client->getResponse()->getContent()
        );
        $crawler = $client->request('GET', $leafLink);
        $this->assertJsonStringEqualsJsonString(
            json_encode(
                [
                    'error' => true,
                ]
            ),
            $client->getResponse()->getContent()
        );
        $crawler = $client->request('GET', $sessionLink);
        $this->assertJsonStringEqualsJsonString(
            json_encode(
                [
                    'status' => 'HANGUP',
                    'reason' => 'WRONG_SESSION'
                ]
            ),
            $client->getResponse()->getContent()
        );
        $session = $this->getSessionfromLink($sessionLink);

        $this->assertResponseIsSuccessful();
    }

    function startWorkflow(KernelBrowser $client)
    {

        $callerLEftService = self::getContainer()->get(CallerLeftService::class);
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '12340';
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $room->setLobby(true);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[1];
        //enter the room and check if the room is okay
        $crawler = $client->request('GET', '/api/v1/lobby/sip/room/' . $id);
        $this->assertResponseIsSuccessful();

        //enter the users pin
        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/' . $id, ['pin' => $caller->getCallerId(), 'caller_id' => '1234']);
        $this->assertResponseIsSuccessful();
        $sessionLink = json_decode($client->getResponse()->getContent(), true)['links']['session'];
        $leafLink = json_decode($client->getResponse()->getContent(), true)['links']['left'];

        //try entering again. the user should not be access again
        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/' . $id, ['pin' => $caller->getCallerId(), 'caller_id' => '1234']);
        $this->assertJsonStringEqualsJsonString(json_encode(['auth_ok' => false, 'links' => []]), $client->getResponse()->getContent());
        $this->assertResponseIsSuccessful();

        $crawler = $client->request('GET', $sessionLink);
        self::assertEquals(
            [
                'status' => "WAITING",
                "reason" => "NOT_ACCEPTED",
                "number_of_participants" => 0,
                "status_of_meeting" => "NOT_STARTED",
                "message" => [],
                'links' => [
                    'session' => $sessionLink,
                    'left' => $leafLink,
                ]
            ],
            json_decode($client->getResponse()->getContent(), true)
        );

        $this->assertResponseIsSuccessful();
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        return [$sessionLink, $leafLink];
    }

    function getLobbyWaitinguser($link): ?LobbyWaitungUser
    {
        $sessionId = explode('=', $link);
        $sessionId = $sessionId[sizeof($sessionId) - 1];
        $sessionRepo = self::getContainer()->get(CallerSessionRepository::class);
        $session = $sessionRepo->findOneBy(['sessionId' => $sessionId]);
        $lobbyUserRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $lobbyUser = $lobbyUserRepo->findOneBy(['uid' => $session->getLobbyWaitingUser()->getUid()]);
        $lobbyUser->setCallerSession($session);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->persist($lobbyUser);
        $manager->flush();
        return $lobbyUser;
    }

    function getSessionfromLink($link): ?CallerSession
    {
        $sessionId = explode('=', $link);
        $sessionId = $sessionId[sizeof($sessionId) - 1];
        $sessionRepo = self::getContainer()->get(CallerSessionRepository::class);
        $session = $sessionRepo->findOneBy(['sessionId' => $sessionId]);
        return $session;
    }
}
