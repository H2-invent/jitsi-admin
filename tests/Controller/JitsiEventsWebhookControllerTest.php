<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class JitsiEventsWebhookControllerTest extends WebTestCase
{
    private function authorizationHeader(): string
    {
        return 'Bearer ' . self::getContainer()->getParameter('JITSI_EVENTS_TOKEN');
    }

    public function testCreateRejectsMissingAuthorization(): void
    {
        $client = static::createClient();
        $client->request('POST', '/jitsi/events/room/created', [], [], [], json_encode(['event_name' => 'unknown']));

        $this->assertResponseStatusCodeSame(401);
        $this->assertSame(['authorized' => false], json_decode($client->getResponse()->getContent(), true));
    }

    public function testCreateHandlesRequest(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/jitsi/events/room/created',
            [],
            [],
            ['HTTP_AUTHORIZATION' => $this->authorizationHeader(), 'CONTENT_TYPE' => 'application/json'],
            json_encode(['event_name' => 'unknown'])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['succes']);
    }

    public function testDestroyHandlesRequest(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/jitsi/events/room/destroyed',
            [],
            [],
            ['HTTP_AUTHORIZATION' => $this->authorizationHeader(), 'CONTENT_TYPE' => 'application/json'],
            json_encode(['event_name' => 'unknown'])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['succes']);
    }

    public function testJoinedHandlesRequest(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/jitsi/events/occupant/joined',
            [],
            [],
            ['HTTP_AUTHORIZATION' => $this->authorizationHeader(), 'CONTENT_TYPE' => 'application/json'],
            json_encode(['event_name' => 'unknown'])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['succes']);
    }

    public function testLeftHandlesRequest(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/jitsi/events/occupant/left',
            [],
            [],
            ['HTTP_AUTHORIZATION' => $this->authorizationHeader(), 'CONTENT_TYPE' => 'application/json'],
            json_encode(['event_name' => 'unknown'])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['succes']);
    }
}
