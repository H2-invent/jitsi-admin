<?php

namespace App\Tests;

use App\Repository\RoomsRepository;
use App\Service\caller\CallerLeftService;
use App\Service\caller\CallerPinService;
use App\Service\caller\CallerPrepareService;
use App\Service\caller\CallerSessionService;
use Doctrine\ORM\EntityManagerInterface;
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

        $this->assertJsonStringEqualsJsonString(json_encode(array('status' => 'HANGUP', 'reason'=>'TO_EARLY','endTime'=>$room->getEndTimestamp(), 'startTime' => $room->getStartTimestamp(),  'links' => array())), $client->getResponse()->getContent());
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
        $this->assertEquals(404,$client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(array('error' => 'MISSING_ARGUMENT', 'argument' => array('pin', 'caller_id'))), $client->getResponse()->getContent());

        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/' . $id, array('pin' => '1234'));
        $this->assertEquals(404,$client->getResponse()->getStatusCode());
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
                'session' => '/api/v1/lobby/sip/session?session_id='.$session->getSessionId(),
                'left' => '/api/v1/lobby/sip/session/left?session_id='.$session->getSessionId()
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

        $crawler = $client->request('GET',  '/api/v1/lobby/sip/session');
        $this->assertEquals(404,$client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(array('error' => 'MISSING_ARGUMENT', 'argument' => array('session_id'))), $client->getResponse()->getContent());

        $crawler = $client->request('GET',  '/api/v1/lobby/sip/session', array('session_id' => $caller->getCallerId()));
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

        $crawler = $client->request('GET',  '/api/v1/lobby/sip/session');
        $this->assertEquals(404,$client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(array('error' => 'MISSING_ARGUMENT', 'argument' => array('session_id'))), $client->getResponse()->getContent());

        $crawler = $client->request('GET',  '/api/v1/lobby/sip/session/left', array('session_id' => $caller->getCallerId()));
        $this->assertResponseIsSuccessful();

    }
    public function testWokflowUserDeclined(): void
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

        $crawler = $client->request('GET',  '/api/v1/lobby/sip/session');
        $this->assertEquals(404,$client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(array('error' => 'MISSING_ARGUMENT', 'argument' => array('session_id'))), $client->getResponse()->getContent());

        $crawler = $client->request('GET',  '/api/v1/lobby/sip/session/left', array('session_id' => $caller->getCallerId()));
        $this->assertResponseIsSuccessful();

    }
}
