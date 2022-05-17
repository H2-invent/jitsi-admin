<?php

namespace App\Tests\SipCaller;

use App\Entity\CallerSession;
use App\Entity\LobbyWaitungUser;
use App\Entity\RoomStatus;
use App\Entity\RoomStatusParticipant;
use App\Repository\CallerSessionRepository;
use App\Repository\LobbyWaitungUserRepository;
use App\Repository\RoomsRepository;
use App\Service\caller\CallerLeftService;
use App\Service\caller\CallerPinService;
use App\Service\caller\CallerPrepareService;
use App\Service\caller\CallerSessionService;
use App\Service\RoomService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CallerControllerTest extends WebTestCase
{
    public function testAuthorizedFalse(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/v1/lobby/sip/room/123419');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(array('authorized' => false)), $client->getResponse()->getContent());

        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/123419');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(array('authorized' => false)), $client->getResponse()->getContent());

        $crawler = $client->request('GET', '/api/v1/lobby/sip/session');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(array('authorized' => false)), $client->getResponse()->getContent());

        $crawler = $client->request('GET', '/api/v1/lobby/sip/session/left');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(array('authorized' => false)), $client->getResponse()->getContent());

    }

    public function testGetCallerRoom(): void
    {
        $client = static::createClient(array(), array('HTTP_authorization' => 'Bearer 123456'));

        $callerLEftService = self::getContainer()->get(CallerLeftService::class);
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 19'));
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];


        $crawler = $client->request('GET', '/api/v1/lobby/sip/room/123419');
        $this->assertResponseIsSuccessful();

        $this->assertJsonStringEqualsJsonString(json_encode(array('status' => 'HANGUP', 'reason' => 'TO_EARLY', 'endTime' => $room->getEndTimestamp(), 'startTime' => $room->getStartTimestamp(), 'links' => array())), $client->getResponse()->getContent());
        $crawler = $client->request('GET', '/api/v1/lobby/sip/room/1234190');
        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString(json_encode(array('status' => 'ROOM_ID_UKNOWN', 'reason' => 'ROOM_ID_UKNOWN', 'links' => array())), $client->getResponse()->getContent());
    }

    public function testGetCallerPin(): void
    {
        $client = static::createClient(array(), array('HTTP_authorization' => 'Bearer 123456'));

        $callerLEftService = self::getContainer()->get(CallerLeftService::class);
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 19'));
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];


        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/' . $id, array());
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(array('error' => 'MISSING_ARGUMENT', 'argument' => array('pin', 'caller_id'))), $client->getResponse()->getContent());

        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/' . $id, array('pin' => '1234'));
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(array('error' => 'MISSING_ARGUMENT', 'argument' => array('caller_id'))), $client->getResponse()->getContent());

        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/' . $id, array('pin' => '1234', 'caller_id' => '1234'));
        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString(json_encode(array(
                'auth_ok' => false,
                'links' => array()
            )
        ), $client->getResponse()->getContent());
        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/12' . $id, array('pin' => '1234', 'caller_id' => '1234'));
        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString(json_encode(array(
                'auth_ok' => false,
                'links' => array()
            )
        ), $client->getResponse()->getContent());

        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/' . $id, array('pin' => $caller->getCallerId(), 'caller_id' => '1234'));
        $this->assertResponseIsSuccessful();
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 19'));
        $caller = $room->getCallerIds()[0];
        $session = $caller->getCallerSession();
        $this->assertJsonStringEqualsJsonString(json_encode(array(
                'auth_ok' => true,
                'links' => array(
                    'session' => '/api/v1/lobby/sip/session?session_id=' . $session->getSessionId(),
                    'left' => '/api/v1/lobby/sip/session/left?session_id=' . $session->getSessionId()
                )
            )
        ), $client->getResponse()->getContent());
    }

    public function testGetCallerSession(): void
    {
        $client = static::createClient(array(), array('HTTP_authorization' => 'Bearer 123456'));

        $callerLEftService = self::getContainer()->get(CallerLeftService::class);
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 19'));
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];

        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/' . $id, array('pin' => $caller->getCallerId(), 'caller_id' => '1234'));
        $this->assertResponseIsSuccessful();
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 19'));
        $caller = $room->getCallerIds()[0];
        $session = $caller->getCallerSession();

        $crawler = $client->request('GET', '/api/v1/lobby/sip/session');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(array('error' => 'MISSING_ARGUMENT', 'argument' => array('session_id'))), $client->getResponse()->getContent());

        $crawler = $client->request('GET', '/api/v1/lobby/sip/session', array('session_id' => $caller->getCallerId()));
        $this->assertResponseIsSuccessful();

    }

    public function testGetCallerLeft(): void
    {
        $client = static::createClient(array(), array('HTTP_authorization' => 'Bearer 123456'));

        $callerLEftService = self::getContainer()->get(CallerLeftService::class);
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 19'));
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];

        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/' . $id, array('pin' => $caller->getCallerId(), 'caller_id' => '1234'));
        $this->assertResponseIsSuccessful();
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 19'));
        $caller = $room->getCallerIds()[0];
        $session = $caller->getCallerSession();

        $crawler = $client->request('GET', '/api/v1/lobby/sip/session');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(array('error' => 'MISSING_ARGUMENT', 'argument' => array('session_id'))), $client->getResponse()->getContent());

        $crawler = $client->request('GET', '/api/v1/lobby/sip/session/left', array('session_id' => $caller->getCallerId()));
        $this->assertResponseIsSuccessful();

    }


    /**
     * @return int|string
     */
    public function testFinishMeeing(): void
    {
        $client = static::createClient(array(), array('HTTP_authorization' => 'Bearer 123456'));

        $res = $this->startWorkflow($client);
        $sessionLink = $res[0];
        $leafLink = $res[1];
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);

        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));
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

        self::assertEquals(array(
                'status' => "WAITING",
                "reason" => "NOT_ACCEPTED",
                "number_of_participants" => 0,
                "status_of_meeting" => "STARTED",
                'links' => array(
                    'session' => $sessionLink,
                    'left' => $leafLink,
                )
            )
            , json_decode($client->getResponse()->getContent(), true));

        $this->assertResponseIsSuccessful();

        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));
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
        self::assertEquals(array(
                'status' => "WAITING",
                "reason" => "NOT_ACCEPTED",
                "number_of_participants" => 1,
                "status_of_meeting" => "STARTED",
                'links' => array(
                    'session' => $sessionLink,
                    'left' => $leafLink,
                )
            )
            , json_decode($client->getResponse()->getContent(), true));
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
        self::assertEquals(array(
                'status' => "WAITING",
                "reason" => "NOT_ACCEPTED",
                "number_of_participants" => 2,
                "status_of_meeting" => "STARTED",
                'links' => array(
                    'session' => $sessionLink,
                    'left' => $leafLink,
                )
            )
            , json_decode($client->getResponse()->getContent(), true));

        $this->assertResponseIsSuccessful();

        $status->setDestroyedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime())
            ->setCreated(false)
            ->setDestroyed(true);
        $manager->persist($status);
        $manager->flush();
        $crawler = $client->request('GET', $sessionLink);
        self::assertEquals(array(
                'status' => "HANGUP",
                "reason" => "MEETING_HAS_FINISHED",
                'links' => array(
                    'left' => $leafLink,
                )
            )
            , json_decode($client->getResponse()->getContent(), true));
        $this->assertResponseIsSuccessful();
    }


    public function testDeclineCaller(): void
    {
        $client = static::createClient(array(), array('HTTP_authorization' => 'Bearer 123456'));
        $res = $this->startWorkflow($client);
        $sessionLink = $res[0];
        $leafLink = $res[1];

        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);


        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));
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
        self::assertEquals(array(
                'status' => "WAITING",
                "reason" => "NOT_ACCEPTED",
                "number_of_participants" => 0,
                "status_of_meeting" => "STARTED",
                'links' => array(
                    'session' => $sessionLink,
                    'left' => $leafLink,
                )
            )
            , json_decode($client->getResponse()->getContent(), true));

        $this->assertResponseIsSuccessful();

        $client->loginUser($room->getModerator());
        $session = $this->getLobbyWaitinguser($sessionLink);;
        $crawler = $client->request('GET', '/room/lobby/decline/' . $session->getUid());
        $crawler = $client->request('GET', $sessionLink);
        self::assertEquals(array(
                'status' => "HANGUP",
                "reason" => "DECLINED",
                'links' => array(
                )
            )
            , json_decode($client->getResponse()->getContent(), true));

        $this->assertResponseIsSuccessful();
    }

    public function testAcceptCaller(): void
    {
        $client = static::createClient(array(), array('HTTP_authorization' => 'Bearer 123456'));
        $res = $this->startWorkflow($client);
        $sessionLink = $res[0];
        $leafLink = $res[1];
        $roomService = self::getContainer()->get(RoomService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);


        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));
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
        self::assertEquals(array(
                'status' => "WAITING",
                "reason" => "NOT_ACCEPTED",
                "number_of_participants" => 0,
                "status_of_meeting" => "STARTED",
                'links' => array(
                    'session' => $sessionLink,
                    'left' => $leafLink,
                )
            )
            , json_decode($client->getResponse()->getContent(), true));

        $this->assertResponseIsSuccessful();

        $client->loginUser($room->getModerator());
        $lobbyWaitinguser = $this->getLobbyWaitinguser($sessionLink);
        $crawler = $client->request('GET', '/room/lobby/accept/' . $lobbyWaitinguser->getUid());
        $crawler = $client->request('GET', $sessionLink);
        $session = $this->getSessionfromLink($sessionLink);
        self::assertEquals(array(
            'status' => 'ACCEPTED',
            'reason' => 'ACCEPTED_BY_MODERATOR',
            'number_of_participants' => 0,
            'status_of_meeting' => 'STARTED',
            'jwt' => $roomService->generateJwt($session->getCaller()->getRoom(), $session->getCaller()->getUser(), $session->getShowName()),
                'links' => array(
                    'session' => $sessionLink,
                    'left' => $leafLink,
                )
            )
            , json_decode($client->getResponse()->getContent(), true));

        $this->assertResponseIsSuccessful();
    }

    public function testAcceptAllCaller(): void
    {
        $client = static::createClient(array(), array('HTTP_authorization' => 'Bearer 123456'));
        $res = $this->startWorkflow($client);
        $sessionLink = $res[0];
        $leafLink = $res[1];
        $roomService = self::getContainer()->get(RoomService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);


        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));
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
        self::assertEquals(array(
                'status' => 'WAITING',
                'reason' => 'NOT_ACCEPTED',
                'number_of_participants' => 0,
                'status_of_meeting' => 'STARTED',
                'links' => array(
                    'session' => $sessionLink,
                    'left' => $leafLink,
                )
            )
            , json_decode($client->getResponse()->getContent(), true));
        $this->assertResponseIsSuccessful();

        $client->loginUser($room->getModerator());
        $lobbyWaitinguser = $this->getLobbyWaitinguser($sessionLink);
        $crawler = $client->request('GET', '/room/lobby/acceptAll/' . $lobbyWaitinguser->getRoom()->getUidReal());
        $crawler = $client->request('GET', $sessionLink);
        $session = $this->getSessionfromLink($sessionLink);
        self::assertEquals(array(
                'status' => 'ACCEPTED',
                'reason' => 'ACCEPTED_BY_MODERATOR',
                'number_of_participants' => 0,
                'status_of_meeting' => 'STARTED',
                'jwt' => $roomService->generateJwt($session->getCaller()->getRoom(), $session->getCaller()->getUser(), $session->getShowName()),
                'links' => array(
                    'session' => $sessionLink,
                    'left' => $leafLink,
                )
            )
            , json_decode($client->getResponse()->getContent(), true));

        $this->assertResponseIsSuccessful();
    }

    public function testCallerLeft(): void
    {
        $client = static::createClient(array(), array('HTTP_authorization' => 'Bearer 123456'));
        $links = $this->startWorkflow($client);
        $sessionLink = $links[0];
        $leafLink = $links[1];
        $roomService = self::getContainer()->get(RoomService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);


        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));
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
        self::assertEquals(array(
                'status' => 'WAITING',
                'reason' => 'NOT_ACCEPTED',
                'number_of_participants' => 0,
                'status_of_meeting' => 'STARTED',
                'links' => array(
                    'session' => $sessionLink,
                    'left' => $leafLink,
                )
            )
            , json_decode($client->getResponse()->getContent(), true));
        $this->assertResponseIsSuccessful();

        $crawler = $client->request('GET', $leafLink);
        $this->assertJsonStringEqualsJsonString(json_encode(array(
            'error' => false,
        )), $client->getResponse()->getContent());
        $crawler = $client->request('GET', $leafLink);
        $this->assertJsonStringEqualsJsonString(json_encode(array(
            'error' => true,
        )), $client->getResponse()->getContent());
        $crawler = $client->request('GET', $sessionLink);
        $this->assertJsonStringEqualsJsonString(json_encode(array(
            'status' => 'HANGUP',
            'reason' => 'WRONG_SESSION'
        )), $client->getResponse()->getContent());
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
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));

        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[1];
        //enter the room and check if the room is okay
        $crawler = $client->request('GET', '/api/v1/lobby/sip/room/' . $id);
        $this->assertResponseIsSuccessful();

        //enter the users pin
        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/' . $id, array('pin' => $caller->getCallerId(), 'caller_id' => '1234'));
        $this->assertResponseIsSuccessful();
        $sessionLink = json_decode($client->getResponse()->getContent(), true)['links']['session'];
        $leafLink = json_decode($client->getResponse()->getContent(), true)['links']['left'];

        //try entering again. the user should not be access again
        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/' . $id, array('pin' => $caller->getCallerId(), 'caller_id' => '1234'));
        $this->assertJsonStringEqualsJsonString(json_encode(array('auth_ok' => false, 'links' => array())), $client->getResponse()->getContent());
        $this->assertResponseIsSuccessful();

        $crawler = $client->request('GET', $sessionLink);
        self::assertEquals(array(
                'status' => "WAITING",
                "reason" => "NOT_ACCEPTED",
                "number_of_participants" => 0,
                "status_of_meeting" => "NOT_STARTED",
                'links' => array(
                    'session' => $sessionLink,
                    'left' => $leafLink,
                )
            )
            , json_decode($client->getResponse()->getContent(), true));

        $this->assertResponseIsSuccessful();
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));
        return array($sessionLink, $leafLink);
    }

    function getLobbyWaitinguser($link): ?LobbyWaitungUser
    {
        $sessionId = explode('=', $link);
        $sessionId = $sessionId[sizeof($sessionId) - 1];
        $sessionRepo = self::getContainer()->get(CallerSessionRepository::class);
        $session = $sessionRepo->findOneBy(array('sessionId' => $sessionId));
        $lobbyUserRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $lobbyUser = $lobbyUserRepo->findOneBy(array('uid' => $session->getLobbyWaitingUser()->getUid()));
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
        $session = $sessionRepo->findOneBy(array('sessionId' => $sessionId));
        return $session;

    }
}
