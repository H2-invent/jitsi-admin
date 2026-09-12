<?php

namespace App\Tests\Controller;

use App\Repository\ServerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ServerAPIControllerTest extends WebTestCase
{
    public function testIndexWithoutAllowedServer(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/server/create', [
            'url' => 'new.example.com',
            'name' => 'New Server',
            'app_id' => 'id',
            'app_secret' => 'secret',
        ], [], ['HTTP_AUTHORIZATION' => 'Bearer unknown-key']);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertSame('No Server found. The server mus be allowed to be cloned to autoscale', $data['text']);
    }

    public function testIndexCreatesClonedServer(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si2']);
        $server->setAllowedToCloneForAutoscale(true);
        $em->persist($server);
        $em->flush();

        $client->request('POST', '/api/v1/server/create', [
            'url' => 'clone.example.com',
            'name' => 'Clone Server',
            'app_id' => 'clone-id',
            'app_secret' => 'clone-secret',
        ], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $server->getApiKey()]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['error']);
        $this->assertTrue($data['sucess']);
        $this->assertNotNull($data['server_id']);

        $clone = $em->getRepository(\App\Entity\Server::class)->find($data['server_id']);
        $this->assertNotNull($clone);
        $this->assertSame('clone.example.com', $clone->getUrl());
        $this->assertSame('Clone Server', $clone->getServerName());
        $this->assertNull($clone->getAdministrator());
    }

    public function testGetRoomsWithoutServer(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/server/getRooms?minutes=120', [], [], ['HTTP_AUTHORIZATION' => 'Bearer unknown-key']);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertSame('No Server found', $data['text']);
    }

    public function testGetRoomsReturnsRoomIds(): void
    {
        $client = static::createClient();
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si2']);

        $client->request('GET', '/api/v1/server/getRooms?minutes=100000', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $server->getApiKey()]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['error']);
        $this->assertTrue($data['success']);
        $this->assertSame($server->getId(), $data['server_id']);
        $this->assertIsArray($data['room_ids']);
    }
}
