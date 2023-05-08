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
        $kernel = self::bootKernel();
        $sendMessage = self::getContainer()->get(SendMessageToWaitingUser::class);
        self::assertEquals('test nachricht', $sendMessage->createMessageFromString('test nachricht', 1));
        self::assertNull($sendMessage->createMessageFromString('test nachricht', 0));
    }

    public function testfromId(): void
    {
        $kernel = self::bootKernel();

        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $message = $messageRepo->findAll();

        $sendMessage = self::getContainer()->get(SendMessageToWaitingUser::class);
        self::assertEquals($message[0]->getText(), $sendMessage->createMesagefromId($message[0]->getId()));
        self::assertNull($sendMessage->createMesagefromId($message[1]->getId()));
    }

    public function testfromMessageIthSocket(): void
    {
        $kernel = self::bootKernel();
        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                self::assertEquals('{"type":"message","message":"test Nachricht","from":"Test1, 1234, User, Test"}', $update->getData());
                self::assertEquals(['lobby_WaitingUser_websocket/c4ca4238a0b923820dcc509a6f75849b'], $update->getTopics());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $sendMessage = self::getContainer()->get(SendMessageToWaitingUser::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        self::assertEquals(true, $sendMessage->sendMessage(md5(1), 'test Nachricht', $user));
    }

    public function testfromIdWIthSocket(): void
    {
        $kernel = self::bootKernel();
        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                self::assertEquals('{"type":"message","message":"Bitte warten!","from":"Test1, 1234, User, Test"}', $update->getData());
                self::assertEquals(['lobby_WaitingUser_websocket/c4ca4238a0b923820dcc509a6f75849b'], $update->getTopics());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $message = $messageRepo->findAll();
        $sendMessage = self::getContainer()->get(SendMessageToWaitingUser::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        self::assertEquals(true, $sendMessage->sendMessage(md5(1), $message[0]->getId(), $user));
    }

    public function testfromIdErrorWIthSocket(): void
    {
        $kernel = self::bootKernel();
        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                self::assertEquals('{"type":"message","message":"never touched"}', $update->getData());
                self::assertEquals(['lobby_WaitingUser_websocket/c4ca4238a0b923820dcc509a6f75849b'], $update->getTopics());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $message = $messageRepo->findAll();
        $sendMessage = self::getContainer()->get(SendMessageToWaitingUser::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        self::assertEquals(false, $sendMessage->sendMessage(md5(1), $message[1]->getId(), $user));
    }

    public function testfromIdINvaliduidSocket(): void
    {
        $kernel = self::bootKernel();
        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                self::assertEquals('{"type":"message","message":"never touched"}', $update->getData());
                self::assertEquals(['lobby_WaitingUser_websocket/c4ca4238a0b923820dcc509a6f75849b'], $update->getTopics());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $message = $messageRepo->findAll();
        $sendMessage = self::getContainer()->get(SendMessageToWaitingUser::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        self::assertEquals(false, $sendMessage->sendMessage('invalid', $message[0]->getId(), $user));
    }
    public function testfromIdINvalidUSerSocket(): void
    {
        $kernel = self::bootKernel();
        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                self::assertEquals('{"type":"message","message":"never touched"}', $update->getData());
                self::assertEquals(['lobby_WaitingUser_websocket/c4ca4238a0b923820dcc509a6f75849b'], $update->getTopics());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $message = $messageRepo->findAll();
        $sendMessage = self::getContainer()->get(SendMessageToWaitingUser::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local3.de']);
        self::assertEquals(false, $sendMessage->sendMessage(md5(1), $message[0]->getId(), $user));
    }
    public function testSendToAllInLobby(): void
    {
        $kernel = self::bootKernel();
        $directSend = $this->getContainer()->get(DirectSendService::class);
        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                self::assertEquals('{"type":"message","message":"Bitte warten!","from":"Test1, 1234, User, Test"}', $update->getData());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $message = $messageRepo->findAll();
        $sendMessage = self::getContainer()->get(SendMessageToWaitingUser::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'Room with Start and no Participants list and Lobby Activated']);
        self::assertEquals(['counter' => 10, 'success' => true], $sendMessage->sendMessageToAllWaitingUser($message[0]->getId(), $user, $room));
    }
}
