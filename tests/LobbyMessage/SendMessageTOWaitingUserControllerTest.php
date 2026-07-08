<?php

namespace App\Tests\LobbyMessage;

use App\Repository\PredefinedLobbyMessagesRepository;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\Lobby\DirectSendService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;

class SendMessageTOWaitingUserControllerTest extends WebTestCase
{
    public function testSendToOne(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $directSend = self::getContainer()->get(DirectSendService::class);
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);


        $message = $messageRepo->findOneBy([]);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $client->loginUser($user);
        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update) use ($message): string {
                $updateData = json_decode($update->getData(), true);
                self::assertIsArray($updateData);
                self::assertArrayHasKey('message', $updateData);
                self::assertSame($message->getText(), $updateData['message']);
                self::assertEquals(['lobby_WaitingUser_websocket/c4ca4238a0b923820dcc509a6f75849b'], $update->getTopics());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $crawler = $client->request('POST', '/room/lobby/message/send', [], [], [], json_encode(['uid' => md5(1), 'message' => $message->getId()]));

        self::assertResponseIsSuccessful();
        self::assertEquals(['error' => false, 'message' => 'Die Nachricht wurde erfolgreich übermittelt.'], json_decode($client->getResponse()->getContent(), true));
    }
    public function testSendToAll(): void
    {
        $client = static::createClient();
        $userrepo = self::getContainer()->get(UserRepository::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $directSend = self::getContainer()->get(DirectSendService::class);
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);

        $user = $userrepo->findOneBy(['email' => 'test@local.de']);
        $room = $roomRepo->findOneBy(['name' => 'Room with Start and no Participants list and Lobby Activated']);
        $message = $messageRepo->findOneBy([]);

        $client->loginUser($user);
        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update) use ($message): string {
                $updateData = json_decode($update->getData(), true);
                self::assertIsArray($updateData);
                self::assertArrayHasKey('message', $updateData);
                self::assertSame($message->getText(), $updateData['message']);
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $crawler = $client->request('POST', '/room/lobby/message/send/all', [], [], [], json_encode(['uid' => $room->getUidReal(), 'message' => $message->getId()]));

        self::assertResponseIsSuccessful();
        self::assertEquals(['error' => false, 'message' => 'Die Nachricht wurde erfolgreich übermittelt.', 'counts' => 10], json_decode($client->getResponse()->getContent(), true));
    }
    public function testSendToAllNoRoom(): void
    {
        $client = static::createClient();
        $directSend = self::getContainer()->get(DirectSendService::class);
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $message = $messageRepo->findOneBy([]);

        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update) use ($message): string {
                $updateData = json_decode($update->getData(), true);
                self::assertIsArray($updateData);
                self::assertArrayHasKey('message', $updateData);
                self::assertSame($message->getText(), $updateData['message']);
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $client->loginUser($user);
        $crawler = $client->request('POST', '/room/lobby/message/send/all', [], [], [], json_encode(['uid' => 'notFound', 'message' => $message->getId()]));

        self::assertResponseIsSuccessful();
        self::assertEquals(['error' => true, 'message' => 'Bei der Übermittlung der Nachricht gab es einen Fehler.'], json_decode($client->getResponse()->getContent(), true));
    }
}
