<?php

namespace App\Tests\Controller;

use App\Entity\PredefinedLobbyMessages;
use App\Repository\UserRepository;
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
            json_encode(['uid' => 'b61e49bf-c885-493c-8f26-89419f9c8b0b', 'message' => $this->getActiveMessageId()])
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
            json_encode(['uid' => '2678924b-b3c6-4d18-8945-466cfcb61355', 'message' => $this->getActiveMessageId()])
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
