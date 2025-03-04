<?php

namespace App\Tests\Lobby\Service;

use App\Entity\LobbyWaitungUser;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\Lobby\DirectSendService;
use App\Service\Lobby\ToParticipantWebsocketService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;

class LobbyToParticipantsTest extends KernelTestCase
{
    public function testInAppRedirect(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $directSend = $this->getContainer()->get(DirectSendService::class);
        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                if (strpos($update->getData(), 'snackbar') > 0) {
                    self::assertEquals('{"type":"snackbar","message":"Sie wurden zu der Konferenz zugelassen und werden in einigen Sekunden weitergeleitet.","color":"success","closeAfter":2000}', $update->getData());
                }
                if (strpos($update->getData(), 'jitsi-meet') > 0) {
                    self::assertEquals('{"type":"redirect","url":"jitsi-meet:\/\/meet.jit.si2\/12313231ghjgfdsdf?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzMTMyMzFnaGpnZmRzZGYiLCJjb250ZXh0Ijp7InVzZXIiOnsibmFtZSI6IlRlc3QyIFVzZXIyIn19LCJtb2RlcmF0b3IiOmZhbHNlfQ.bG9vHOHTwbMEAFPgg0XxrZtxfYyqwMUN-Rxv6l6psRE#config.subject=%22this_is_a_room_with_lobby%22","timeout":5000}', $update->getData());
                }
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $lobbyToParticipant = self::getContainer()->get(ToParticipantWebsocketService::class);
        $lobbyToParticipant->setDirectSend($directSend);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'This is a room with Lobby']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $lobbyUser = new LobbyWaitungUser();
        $lobbyUser->setType('a');
        $lobbyUser->setUser($user2);
        $lobbyUser->setRoom($room);
        $lobbyUser->setUid('lkjhdslkfjhdskjhfkds');
        $lobbyUser->setCreatedAt(new \DateTime());
        $lobbyUser->setShowName($user2->getFirstName() . ' ' . $user2->getLastName());
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->persist($lobbyUser);
        $manager->flush();
        $lobbyToParticipant->acceptLobbyUser($lobbyUser);
    }

    public function testInBrowserCorsRedirect(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $directSend = $this->getContainer()->get(DirectSendService::class);
        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                if (strpos($update->getData(), 'snackbar') > 0) {
                    self::assertEquals('{"type":"snackbar","message":"Sie wurden zu der Konferenz zugelassen und werden in einigen Sekunden weitergeleitet.","color":"success","closeAfter":2000}', $update->getData());
                }
                if (strpos($update->getData(), 'jitsi-meet') > 0) {
                    self::assertEquals('{"type":"redirect","url":"https:\/\/meet.jit.si2\/12313231ghjgfdsdf?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzMTMyMzFnaGpnZmRzZGYiLCJjb250ZXh0Ijp7InVzZXIiOnsibmFtZSI6IlRlc3QgVXNlciJ9fSwibW9kZXJhdG9yIjpmYWxzZX0.9ND7c-K_wWEciD3NQZiDX-Bhn4jY_XDnqiZXquRpHD4#config.subject=%22This_is_a_room_with_Lobby%22","timeout":5000}', $update->getData());
                }
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $lobbyToParticipant = self::getContainer()->get(ToParticipantWebsocketService::class);
        $lobbyToParticipant->setDirectSend($directSend);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'This is a room with Lobby']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $lobbyUser = new LobbyWaitungUser();
        $lobbyUser->setType('b');
        $lobbyUser->setUser($user2);
        $lobbyUser->setRoom($room);
        $lobbyUser->setUid('lkjhdslkfjhdskjhfkds');
        $lobbyUser->setCreatedAt(new \DateTime());
        $lobbyUser->setShowName($user2->getFirstName() . ' ' . $user2->getLastName());
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $server = $lobbyUser->getRoom()->getServer();
        $server->setCorsHeader(true);
        $manager->persist($server);
        $manager->persist($lobbyUser);
        $manager->flush();
        $lobbyToParticipant->acceptLobbyUser($lobbyUser);
    }

    public function testInBrowser(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'This is a room with Lobby']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $lobbyUser = new LobbyWaitungUser();
        $lobbyUser->setType('b');
        $lobbyUser->setUser($user2);
        $lobbyUser->setRoom($room);
        $lobbyUser->setUid('lkjhdslkfjhdskjhfkds');
        $lobbyUser->setCreatedAt(new \DateTime());
        $lobbyUser->setShowName($user2->getFirstName() . ' ' . $user2->getLastName());
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->persist($lobbyUser);
        $manager->flush();

        $directSend = $this->getContainer()->get(DirectSendService::class);

        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                if (strpos($update->getData(), 'newJitsi') > 0) {
                    self::assertEquals(
                        [
                            'type' => "newJitsi",
                            'options' => [

                                    'jwt' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzMTMyMzFnaGpnZmRzZGYiLCJjb250ZXh0Ijp7InVzZXIiOnsibmFtZSI6IlRlc3QyIFVzZXIyIn19LCJtb2RlcmF0b3IiOmZhbHNlfQ.bG9vHOHTwbMEAFPgg0XxrZtxfYyqwMUN-Rxv6l6psRE',
                                ],
                        ],
                        json_decode($update->getData(), true)
                    );
                }
                if (strpos($update->getData(), 'snackbar') > 0) {
                    self::assertEquals('{"type":"snackbar","message":"Sie wurden zu der Konferenz zugelassen und werden in einigen Sekunden weitergeleitet.","color":"success","closeAfter":2000}', $update->getData());
                }
                self::assertEquals(['lobby_WaitingUser_websocket/lkjhdslkfjhdskjhfkds'], $update->getTopics());

                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $lobbyToParticipant = self::getContainer()->get(ToParticipantWebsocketService::class);
        $lobbyToParticipant->setDirectSend($directSend);
        $lobbyToParticipant->acceptLobbyUser($lobbyUser);
    }

    public function testInBrowserWithPrefix(): void
    {
        $kernel = self::bootKernel();
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'This is a room with Lobby']);
        $room->getServer()->setPrefixRoomUidWithHash(true);
        $manager->persist($room);
        $manager->flush();
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $lobbyUser = new LobbyWaitungUser();
        $lobbyUser->setType('b');
        $lobbyUser->setUser($user2);
        $lobbyUser->setRoom($room);
        $lobbyUser->setUid('lkjhdslkfjhdskjhfkds');
        $lobbyUser->setCreatedAt(new \DateTime());
        $lobbyUser->setShowName($user2->getFirstName() . ' ' . $user2->getLastName());
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->persist($lobbyUser);
        $manager->flush();

        $directSend = $this->getContainer()->get(DirectSendService::class);

        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                if (strpos($update->getData(), 'newJitsi') > 0) {
                    self::assertEquals(
                        [
                            'type' => "newJitsi",
                            'options' => [
                                    'jwt' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzMTMyMzFnaGpnZmRzZGYiLCJjb250ZXh0Ijp7InVzZXIiOnsibmFtZSI6IlRlc3QyIFVzZXIyIn19LCJtb2RlcmF0b3IiOmZhbHNlfQ.bG9vHOHTwbMEAFPgg0XxrZtxfYyqwMUN-Rxv6l6psRE',
                            ]
                        ],
                        json_decode($update->getData(), true)
                    );
                }
                if (strpos($update->getData(), 'snackbar') > 0) {
                    self::assertEquals('{"type":"snackbar","message":"Sie wurden zu der Konferenz zugelassen und werden in einigen Sekunden weitergeleitet.","color":"success","closeAfter":2000}', $update->getData());
                }
                self::assertEquals(['lobby_WaitingUser_websocket/lkjhdslkfjhdskjhfkds'], $update->getTopics());

                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $lobbyToParticipant = self::getContainer()->get(ToParticipantWebsocketService::class);
        $lobbyToParticipant->setDirectSend($directSend);
        $lobbyToParticipant->acceptLobbyUser($lobbyUser);
    }


}
