<?php

namespace App\Tests\JitsiEvents\controller;

use App\Tests\JitsiEvents\service\JitsiEventsServiceTest;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class JitsiWebhookControllerTest extends WebTestCase
{
    public function testAuthenticationSuccess(): void
    {
        $client = static::createClient([],['HTTP_AUTHORIZATION' => 'Bearer 123456']);
        $crawler = $client->request('POST', '/jitsi/events/room/created',);

        $this->assertResponseIsSuccessful();
    }
    public function testAuthenticationFailure(): void
    {
        $client = static::createClient([],['HTTP_AUTHORIZATION' => 'Bearer abcdef']);
        $crawler = $client->request('POST', '/jitsi/events/room/created',);

        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }
    public function testCreateRoom(): void
    {
        $client = static::createClient([],['HTTP_AUTHORIZATION' => 'Bearer 123456']);
        $crawler = $client->jsonRequest('POST', '/jitsi/events/room/created',JitsiEventsServiceTest::$roomCreatedData);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), 'Invalid JSON response');
        $this->assertJsonStringEqualsJsonString('{"success":true}', $client->getResponse()->getContent());
    }
    public function testDestroyRoom(): void
    {
        $client = static::createClient([],['HTTP_AUTHORIZATION' => 'Bearer 123456']);
        $crawler = $client->jsonRequest('POST', '/jitsi/events/room/created',JitsiEventsServiceTest::$roomCreatedData);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), 'Invalid JSON response');
        $this->assertJsonStringEqualsJsonString('{"success":true}', $client->getResponse()->getContent());
        $crawler = $client->jsonRequest('POST', '/jitsi/events/room/destroyed',JitsiEventsServiceTest::$roomDestroyedData);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), 'Invalid JSON response');
        $this->assertJsonStringEqualsJsonString('{"success":true}', $client->getResponse()->getContent());
    }
    public function testJoinRoom(): void
    {
        $client = static::createClient([],['HTTP_AUTHORIZATION' => 'Bearer 123456']);
        $crawler = $client->jsonRequest('POST', '/jitsi/events/room/created',JitsiEventsServiceTest::$roomCreatedData);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), 'Invalid JSON response');
        $this->assertJsonStringEqualsJsonString('{"success":true}', $client->getResponse()->getContent());

        $crawler = $client->jsonRequest('POST', '/jitsi/events/occupant/joined',JitsiEventsServiceTest::$participantJoinedData);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), 'Invalid JSON response');
        $this->assertJsonStringEqualsJsonString('{"success":true}', $client->getResponse()->getContent());

        $crawler = $client->jsonRequest('POST', '/jitsi/events/occupant/left',JitsiEventsServiceTest::$participantLeftD);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), 'Invalid JSON response');
        $this->assertJsonStringEqualsJsonString('{"success":true}', $client->getResponse()->getContent());

        $crawler = $client->jsonRequest('POST', '/jitsi/events/room/destroyed',JitsiEventsServiceTest::$roomDestroyedData);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), 'Invalid JSON response');
        $this->assertJsonStringEqualsJsonString('{"success":true}', $client->getResponse()->getContent());
    }
    public function testErrorRoom(): void
    {
        $client = static::createClient([],['HTTP_AUTHORIZATION' => 'Bearer 123456']);
        $crawler = $client->jsonRequest('POST', '/jitsi/events/room/created',JitsiEventsServiceTest::$roomDestroyedData);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), 'Invalid JSON response');
        $this->assertJsonStringEqualsJsonString('{"succes":false,"error":"Room Jitsi ID not found"}', $client->getResponse()->getContent());

        $crawler = $client->jsonRequest('POST', '/jitsi/events/occupant/joined',JitsiEventsServiceTest::$participantLeftD);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), 'Invalid JSON response');
        $this->assertJsonStringEqualsJsonString('{"succes":false,"error":"Wrong occupant ID. The occupant is not in the database"}', $client->getResponse()->getContent());

        $crawler = $client->jsonRequest('POST', '/jitsi/events/occupant/left',JitsiEventsServiceTest::$participantJoinedData);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), 'Invalid JSON response');
        $this->assertJsonStringEqualsJsonString('{"succes":false,"error":"Room Jitsi ID not found"}', $client->getResponse()->getContent());

        $crawler = $client->jsonRequest('POST', '/jitsi/events/room/destroyed',JitsiEventsServiceTest::$participantJoinedData);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), 'Invalid JSON response');
        $this->assertJsonStringEqualsJsonString('{"succes":false,"error":"Room Jitsi ID not found"}', $client->getResponse()->getContent());
    }
}
