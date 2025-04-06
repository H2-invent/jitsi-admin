<?php


namespace App\Tests\Controller;


use App\Entity\Rooms;
use Livekit\Room;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;

class ApiMoveRoomToOtherServerControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testRoomNotFound()
    {
        $this->createRoomWithServer('correctToken');
        $this->client->request('POST', '/api/v1/room/move/notValid', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer someKey'
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        self::assertTrue($data['error']);
        self::assertEquals('Room not found', $data['message']);

    }

    public function testAccessDenied()
    {
        $this->createRoomWithServer('correctToken');
        // Beispiel: Room mit ID 1 hat nicht diesen API-Key
        $this->client->request('POST', '/api/v1/room/move/testUidReal', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer wrongApiKey'
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        self::assertTrue($data['error']);
        self::assertEquals('Access denied', $data['message']);

    }

    public function testNewServerNotFound()
    {
        // Hier muss Room existieren und API-Key korrekt sein
        $room = $this->createRoomWithServer('validKey');

        $this->client->request('POST', '/api/v1/room/move/testUidReal', [
            'serverId' => 99999,
        ], [], [
            'HTTP_AUTHORIZATION' => 'Bearer validKey'
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        self::assertTrue($data['error']);
        self::assertEquals('New Server not found', $data['message']);

    }

    public function testRoomMovedSuccessfully()
    {
        $originalServer = $this->createServer('validKey');
        $newServer = $this->createServer('validKey');

        $room = $this->createRoomWithServer('123321');
        $room->setServer($originalServer);
        $this->entityManager->persist($room);
        $this->entityManager->flush();

        $this->client->request('POST', '/api/v1/room/move/testUidReal', [
            'serverId' => $newServer->getId()
        ], [], [
            'HTTP_AUTHORIZATION' => 'Bearer validKey'
        ]);

        $this->assertResponseIsSuccessful();
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        self::assertFalse($data['error']);
        self::assertEquals('Room moved', $data['message']);


        // Optional: prüfen, ob der Raum wirklich verschoben wurde
        $this->entityManager->refresh($room);
        $this->assertEquals($newServer->getId(), $room->getServer()->getId());
    }
    public function testRoomMovedFailed()
    {
        $originalServer = $this->createServer('validKey');
        $newServer = $this->createServer('validKey');

        $room = $this->createRoomWithServer('123321');
        $room->setServer($originalServer);
        $this->entityManager->persist($room);
        $this->entityManager->flush();

        $this->client->request('POST', '/api/v1/room/move/testUidReal' , [
            'serverId' => $newServer->getId()
        ], [], [
            'HTTP_AUTHORIZATION' => 'Bearer invalidKey'
        ]);

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        self::assertTrue($data['error']);
        self::assertEquals('Access denied', $data['message']);


    }

    private function createServer(string $apiKey): Server
    {
        $server = new Server();
        $server->setApiKey($apiKey);
        $server->setUrl('eintest.com')
        ->setSlug('testslug')
        ->setJwtModeratorPosition(0)
        ->setServerName('mein server');
        $this->entityManager->persist($server);
        $this->entityManager->flush();
        return $server;
    }

    private function createRoomWithServer(string $apiKey): Rooms
    {
        $server = $this->createServer($apiKey);
        $room = new Rooms();
        $room->setName('meinRaum')
        ->setUid('kjsdhf')
        ->setDuration('123')
        ->setSequence(0)
        ->setUidReal('testUidReal');
        $room->setServer($server);
        $this->entityManager->persist($room);
        $this->entityManager->flush();
        return $room;
    }
}