<?php

namespace App\Tests\API;

use App\Repository\RoomsRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ApiTest extends WebTestCase
{
    public function testcreateRoomSucess(): void
    {
        $client = static::createClient(
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer:TestApi',
            ]
        );
        $crawler = $client->request(
            'POST',
            '/api/v1/room',
            [
                'email' => 'test@local.de',
                'name' => 'TestApi',
                'duration' => 70,
                'server' => 'meet.jit.si2',
                'start' => (new \DateTime())->format('Y-m-d') . 'T' . (new \DateTime())->format('H:i'),
                'keycloakId' => '123456'
            ]
        );
        $this->assertEquals(false, json_decode($client->getResponse()->getContent(), true)['error']);
        $this->assertEquals('Meeting erfolgreich angelegt', json_decode($client->getResponse()->getContent(), true)['text']);
    }
    public function testcreateRoomRoom(): void
    {
        $client = static::createClient(
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer:TestApi',
            ]
        );
        $crawler = $client->request(
            'POST',
            '/api/v1/room',
            [
                'email' => 'test@local.de',
                'duration' => 70,
                'server' => 'meet.jit.si2',
                'start' => (new \DateTime())->format('Y-m-d') . 'T' . (new \DateTime())->format('H:i'),
                'keycloakId' => '123456'
            ]
        );

        $this->assertEquals(true, json_decode($client->getResponse()->getContent(), true)['error']);
    }
    public function testgetOptions(): void
    {
        $client = static::createClient(
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer:TestApi',
            ]
        );
        $crawler = $client->request(
            'POST',
            '/api/v1/room',
            [
                'email' => 'test@local.de',
                'name' => 'TestApi',
                'duration' => 70,
                'server' => 'meet.jit.si2',
                'start' => (new \DateTime())->format('Y-m-d') . 'T' . (new \DateTime())->format('H:i'),
                'keycloakId' => '123456'
            ]
        );

        $this->assertEquals(false, json_decode($client->getResponse()->getContent(), true)['error']);
        $this->assertEquals('Meeting erfolgreich angelegt', json_decode($client->getResponse()->getContent(), true)['text']);
        $uid = json_decode($client->getResponse()->getContent(), true)['uid'];
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $urlGenerator = $this->getContainer()->get(UrlGeneratorInterface::class);
        $room = $roomRepo->findOneBy(['uidReal' => $uid]);
        $crawler = $client->request('GET', '/api/v1/info/' . $uid);
        $this->assertEquals('TestApi', json_decode($client->getResponse()->getContent(), true)['name']);
        $this->assertEquals('test@local.de', json_decode($client->getResponse()->getContent(), true)['moderator']);
        $this->assertEquals(70, json_decode($client->getResponse()->getContent(), true)['duration']);
        $this->assertEquals('meet.jit.si2', json_decode($client->getResponse()->getContent(), true)['server']);
        $this->assertEquals($room->getStart()->format('Y-m-dTH:i:s'), json_decode($client->getResponse()->getContent(), true)['start']);
        $this->assertEquals($room->getEnddate()->format('Y-m-dTH:i:s'), json_decode($client->getResponse()->getContent(), true)['end']);
        $this->assertEquals($urlGenerator->generate('room_join', ['room' => $room->getId(), 't' => 'b'], UrlGeneratorInterface::ABSOLUTE_URL), json_decode($client->getResponse()->getContent(), true)['joinBrowser']);
        $this->assertEquals($urlGenerator->generate('room_join', ['room' => $room->getId(), 't' => 'a'], UrlGeneratorInterface::ABSOLUTE_URL), json_decode($client->getResponse()->getContent(), true)['joinApp']);
    }
}
