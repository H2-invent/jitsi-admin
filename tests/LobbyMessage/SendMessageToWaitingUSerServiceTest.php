<?php

namespace App\Tests\LobbyMessage;

use App\Repository\PredefinedLobbyMessagesRepository;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\Lobby\DirectSendService;
use App\Service\Lobby\SendMessageToWaitingUser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;

class SendMessageToWaitingUSerServiceTest extends KernelTestCase
{
    public function testfromMessage(): void
    {
        self::bootKernel();
        $sendMessage = self::getContainer()->get(SendMessageToWaitingUser::class);
        self::assertEquals('test nachricht', $sendMessage->createMessageFromString('test nachricht', 1));
        self::assertNull($sendMessage->createMessageFromString('test nachricht', 0));
    }

    public function testfromId(): void
    {
        self::bootKernel();

        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $message = $messageRepo->findAll();

        $sendMessage = self::getContainer()->get(SendMessageToWaitingUser::class);
        self::assertEquals($message[0]->getText(), $sendMessage->createMesagefromId($message[0]->getId()));
        self::assertNull($sendMessage->createMesagefromId($message[1]->getId()));
    }

    public function testfromMessageIthSocket(): void
    {
        self::bootKernel();
        $directSend = $this->getContainer()->get(DirectSendService::class);
        $sendMessage = self::getContainer()->get(SendMessageToWaitingUser::class);
        $userRepo = self::getContainer()->get(UserRepository::class);

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $message = 'test Nachricht';

        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update) use ($message): string {
                $updateData = json_decode($update->getData(), true);
                self::assertIsArray($updateData);
                self::assertArrayHasKey('message', $updateData);
                self::assertSame($message, $updateData['message']);
                self::assertEquals(['lobby_WaitingUser_websocket/9e1d7a68-2bfd-4923-8f54-3fb7dbd87142'], $update->getTopics());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $sendResult = $sendMessage->sendMessage('9e1d7a68-2bfd-4923-8f54-3fb7dbd87142', $message, $user);

        self::assertTrue($sendResult);
    }

    public function testfromIdWIthSocket(): void
    {
        self::bootKernel();
        $directSend = $this->getContainer()->get(DirectSendService::class);
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $sendMessage = self::getContainer()->get(SendMessageToWaitingUser::class);
        $userRepo = self::getContainer()->get(UserRepository::class);

        $lobbyMessage = $messageRepo->findOneBy([]);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $lobbyMessage = $messageRepo->findOneBy(['id' => $lobbyMessage->getId(), 'active' => true]);

        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update) use ($lobbyMessage): string {
                $updateData = json_decode($update->getData(), true);
                self::assertIsArray($updateData);
                self::assertArrayHasKey('message', $updateData);
                self::assertSame($lobbyMessage->getText(), $updateData['message']);
                self::assertEquals(['lobby_WaitingUser_websocket/9e1d7a68-2bfd-4923-8f54-3fb7dbd87142'], $update->getTopics());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $sendResult = $sendMessage->sendMessage('9e1d7a68-2bfd-4923-8f54-3fb7dbd87142', $lobbyMessage->getId(), $user);

        self::assertTrue($sendResult);
    }

    public function testfromIdErrorWIthSocket(): void
    {
        self::bootKernel();
        $directSend = $this->getContainer()->get(DirectSendService::class);
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $sendMessage = self::getContainer()->get(SendMessageToWaitingUser::class);
        $userRepo = self::getContainer()->get(UserRepository::class);

        $message = $messageRepo->findAll();
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                self::assertEquals('{"type":"message","message":"never touched"}', $update->getData());
                self::assertEquals(['lobby_WaitingUser_websocket/9e1d7a68-2bfd-4923-8f54-3fb7dbd87142'], $update->getTopics());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);

        self::assertEquals(false, $sendMessage->sendMessage('9e1d7a68-2bfd-4923-8f54-3fb7dbd87142', $message[1]->getId(), $user));
    }

    public function testfromIdINvaliduidSocket(): void
    {
        self::bootKernel();
        $directSend = self::getContainer()->get(DirectSendService::class);
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $sendMessage = self::getContainer()->get(SendMessageToWaitingUser::class);
        $userRepo = self::getContainer()->get(UserRepository::class);

        $message = $messageRepo->findAll();
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                self::assertEquals('{"type":"message","message":"never touched"}', $update->getData());
                self::assertEquals(['lobby_WaitingUser_websocket/9e1d7a68-2bfd-4923-8f54-3fb7dbd87142'], $update->getTopics());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);

        self::assertEquals(false, $sendMessage->sendMessage('invalid', $message[0]->getId(), $user));
    }
    public function testfromIdINvalidUSerSocket(): void
    {
        self::bootKernel();
        $directSend = self::getContainer()->get(DirectSendService::class);
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $sendMessage = self::getContainer()->get(SendMessageToWaitingUser::class);
        $userRepo = self::getContainer()->get(UserRepository::class);

        $message = $messageRepo->findAll();
        $user = $userRepo->findOneBy(['email' => 'test@local3.de']);

        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                self::assertEquals('{"type":"message","message":"never touched"}', $update->getData());
                self::assertEquals(['lobby_WaitingUser_websocket/9e1d7a68-2bfd-4923-8f54-3fb7dbd87142'], $update->getTopics());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);

        self::assertEquals(false, $sendMessage->sendMessage('9e1d7a68-2bfd-4923-8f54-3fb7dbd87142', $message[0]->getId(), $user));
    }
    public function testSendToAllInLobby(): void
    {
        self::bootKernel();
        $directSend = self::getContainer()->get(DirectSendService::class);
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $sendMessage = self::getContainer()->get(SendMessageToWaitingUser::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $room = $roomRepo->findOneBy(['name' => 'Room with Start and no Participants list and Lobby Activated']);
        $lobbyMessage = $messageRepo->findOneBy([]);

        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update) use ($lobbyMessage): string {
                $updateData = json_decode($update->getData(), true);
                self::assertIsArray($updateData);
                self::assertArrayHasKey('message', $updateData);
                self::assertSame($lobbyMessage->getText(), $updateData['message']);
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $messageResult = $sendMessage->sendMessageToAllWaitingUser($lobbyMessage->getId(), $user, $room);

        self::assertEquals(['counter' => 10, 'success' => true], $messageResult);
    }
}
