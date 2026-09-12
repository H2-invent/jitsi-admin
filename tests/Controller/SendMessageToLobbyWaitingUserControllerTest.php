<?php

namespace App\Tests\Controller;

use App\Entity\PredefinedLobbyMessages;
use App\Repository\UserRepository;
use App\Tests\Support\LobbyRoom;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SendMessageToLobbyWaitingUserControllerTest extends WebTestCase
{
    private function getActiveMessageId(): int
    {
        $message = self::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(PredefinedLobbyMessages::class)
            ->findOneBy(['active' => true]);
        return $message->getId();
    }

    public function testIndexSendsMessageToWaitingUser(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request(
            'POST',
            '/room/lobby/message/send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['uid' => md5(0), 'message' => $this->getActiveMessageId()])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['error']);
    }

    public function testIndexWithUnknownWaitingUserReturnsError(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request(
            'POST',
            '/room/lobby/message/send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['uid' => 'unknown-uid', 'message' => $this->getActiveMessageId()])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);
    }

    public function testSendToAllSendsMessageToAllWaitingUsers(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request(
            'POST',
            '/room/lobby/message/send/all',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['uid' => LobbyRoom::UID_REAL, 'message' => $this->getActiveMessageId()])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['error']);
        $this->assertSame(10, $data['counts']);
    }

    public function testSendToAllWithUnknownRoomReturnsError(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request(
            'POST',
            '/room/lobby/message/send/all',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['uid' => 'unknown-room', 'message' => $this->getActiveMessageId()])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);
    }
}
