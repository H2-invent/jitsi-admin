<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Rooms;
use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

class ApiMiddlewareControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private array $entitiesToCleanup = [];

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->catchExceptions(false);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function tearDown(): void
    {
        // Remove in reverse order to handle dependencies
        foreach (array_reverse($this->entitiesToCleanup) as $entity) {
            try {
                $this->entityManager->remove($entity);
            } catch (\Exception $e) {
                // Entity may already be removed or not managed
            }
        }
        $this->entityManager->flush();
        $this->entitiesToCleanup = [];

        parent::tearDown();
    }

    private function persistAndTrack(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entitiesToCleanup[] = $entity;
    }

    public function testRoomDeletedMissingParameters(): void
    {
        $this->client->request('POST', '/api/v1/middleware/room-deleted');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertTrue($data['error']);
    }

    public function testRoomDeletedMissingHost(): void
    {
        $this->client->request('POST', '/api/v1/middleware/room-deleted', [
            'key' => 'testKey',
            'jwt' => 'testJwt',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertTrue($data['error']);
    }

    public function testRoomDeletedMissingKey(): void
    {
        $this->client->request('POST', '/api/v1/middleware/room-deleted', [
            'host' => 'testHost',
            'jwt' => 'testJwt',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertTrue($data['error']);
    }

    public function testRoomDeletedMissingJwt(): void
    {
        $this->client->request('POST', '/api/v1/middleware/room-deleted', [
            'host' => 'testHost',
            'key' => 'testKey',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertTrue($data['error']);
    }

    public function testRoomDeletedServerNotFound(): void
    {
        $this->client->request('POST', '/api/v1/middleware/room-deleted', [
            'host' => 'nonexistent.server.local',
            'key' => 'nonexistentKey',
            'jwt' => 'testJwt',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertTrue($data['error']);
    }

    public function testRoomDeletedProvisioningDisabled(): void
    {
        $server = $this->createServer(false);
        $room = $this->createRoom($server);
        $jwt = $this->createValidJwt($room->getUid(), $server->getAppSecret());

        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.provisioner_request');

        $this->client->request('POST', '/api/v1/middleware/room-deleted', [
            'host' => $server->getUrl(),
            'key' => $server->getAppId(),
            'jwt' => $jwt,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertFalse($data['error']);


        // Verify deletion message was not dispatched
        $sentMessages = $transport->getSent();
        self::assertCount(0, $sentMessages);
    }

    public function testRoomDeletedInvalidJwt(): void
    {
        $server = $this->createServer(true);

        $this->client->request('POST', '/api/v1/middleware/room-deleted', [
            'host' => $server->getUrl(),
            'key' => $server->getAppId(),
            'jwt' => 'invalid.jwt.token',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertTrue($data['error']);
    }

    public function testRoomDeletedJwtWithWrongSecret(): void
    {
        $server = $this->createServer(true);
        $jwt = $this->createValidJwt('test-room-uid', 'wrongSecret');

        $this->client->request('POST', '/api/v1/middleware/room-deleted', [
            'host' => $server->getUrl(),
            'key' => $server->getAppId(),
            'jwt' => $jwt,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertTrue($data['error']);
    }

    public function testRoomDeletedRoomNotFound(): void
    {
        $server = $this->createServer(true);
        $jwt = $this->createValidJwt('nonexistent-room-uid', $server->getAppSecret());

        $this->client->request('POST', '/api/v1/middleware/room-deleted', [
            'host' => $server->getUrl(),
            'key' => $server->getAppId(),
            'jwt' => $jwt,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertTrue($data['error']);
    }

    public function testRoomDeletedSuccess(): void
    {
        $server = $this->createServer(true);
        $room = $this->createRoom($server);
        $jwt = $this->createValidJwt($room->getUid(), $server->getAppSecret());

        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.provisioner_request');

        $this->client->request('POST', '/api/v1/middleware/room-deleted', [
            'host' => $server->getUrl(),
            'key' => $server->getAppId(),
            'jwt' => $jwt,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertFalse($data['error']);

        // Verify deletion message was dispatched
        $sentMessages = $transport->getSent();
        self::assertCount(1, $sentMessages);
    }

    private function createServer(bool $provisioningEnabled): Server
    {
        $uniqueId = uniqid('', true);
        $server = new Server();
        $server->setUrl('test-server-' . $uniqueId . '.local');
        $server->setAppId('testAppId-' . $uniqueId);
        $server->setAppSecret('testAppSecret-' . $uniqueId);
        $server->setSlug('test-slug-' . $uniqueId);
        $server->setJwtModeratorPosition(0);
        $server->setServerName('Test Server ' . $uniqueId);
        $server->setIsProvisioningEnabled($provisioningEnabled);
        $this->persistAndTrack($server);
        $this->entityManager->flush();

        return $server;
    }

    private function createRoom(Server $server): Rooms
    {
        $uniqueId = uniqid('', true);
        $room = new Rooms();
        $room->setName('Test Room ' . $uniqueId);
        $room->setUid('test-room-uid-' . $uniqueId);
        $room->setUidReal('test-room-uid-real-' . $uniqueId);
        $room->setDuration(60);
        $room->setSequence(0);
        $room->setServer($server);
        $this->persistAndTrack($room);
        $this->entityManager->flush();

        return $room;
    }

    private function createValidJwt(string $roomName, string $secret): string
    {
        $payload = [
            'room' => $roomName,
            'iat' => time(),
            'exp' => time() + 3600,
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }
}

