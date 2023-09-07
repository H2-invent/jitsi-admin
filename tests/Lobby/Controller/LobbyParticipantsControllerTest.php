<?php

namespace App\Tests\Lobby\Controller;

use App\Repository\LobbyWaitungUserRepository;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\Lobby\DirectSendService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LobbyParticipantsControllerTest extends WebTestCase
{
    public function testLobby(): void
    {
        $client = static::createClient();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'This is a room with Lobby']);
        $moderator = $room->getModerator();
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($moderator);
        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $directSend = $this->getContainer()->get(DirectSendService::class);
        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $lobbyUSerRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        self::assertNull($lobbyUSerRepo->findOneBy(['user' => $user2, 'room' => $room]));
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
        $url = $urlGenerator->generate('lobby_participants_wait', ['roomUid' => $room->getUidReal(), 'userUid' => $user2->getUid()]);
        $crawler = $client->request('GET', $url);
        $lobbyUser = $lobbyUSerRepo->findOneBy(['user' => $user2, 'room' => $room]);
        self::assertNotNull($lobbyUser);
        self::assertFalse($lobbyUser->isWebsocketReady());
        $this->assertEquals(
            1,
            $crawler->filter('.overlay:contains("Bitte warten Sie. Der Moderator wurde informiert und lässt Sie eintreten.")')->count()
        );
        $this->assertEquals(
            3,
            $crawler->filter('.lobbyPart')->count()
        );
        $this->assertEquals(
            0,
            $crawler->filter('.lobbyOnlinePart')->count()
        );
        $crawler = $client->request('GET', '/lobby/websocket/ready/' . $lobbyUser->getUid());
        $lobbyUser = $lobbyUSerRepo->findOneBy(['user' => $user2, 'room' => $room]);
        self::assertTrue($lobbyUser->isWebsocketReady());

    }

    public function testRenew(): void
    {
        $client = static::createClient();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'This is a room with Lobby']);
        $moderator = $room->getModerator();
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($moderator);
        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $directSend = $this->getContainer()->get(DirectSendService::class);
        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $lobbyUSerRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
        $urlRenew = $urlGenerator->generate('lobby_participants_renew', ['userUid' => 'test']);
        $crawler = $client->request('GET', $urlRenew);
        self::assertEquals('{"error":true,"message":"Fehler"}', $client->getResponse()->getContent());
        self::assertNull($lobbyUSerRepo->findOneBy(['user' => $user2, 'room' => $room]));
        $url = $urlGenerator->generate('lobby_participants_wait', ['roomUid' => $room->getUidReal(), 'userUid' => $user2->getUid()]);

        $crawler = $client->request('GET', $url);
        $lobbyUser = $lobbyUSerRepo->findOneBy(['user' => $user2, 'room' => $room]);
        $urlRenew = $urlGenerator->generate('lobby_participants_renew', ['userUid' => $lobbyUser->getUid()]);
        $this->assertStringContainsString('href="' . $urlRenew, $client->getResponse()->getContent());
        self::assertNotNull($lobbyUser);
        $crawler = $client->request('GET', $urlRenew);
        self::assertEquals('{"error":false,"message":"Sie haben Ihren Beitritt erfolgreich angefordert.","color":"success"}', $client->getResponse()->getContent());
    }

    public function testLeave(): void
    {
        $client = static::createClient();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'This is a room with Lobby']);
        $moderator = $room->getModerator();
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($moderator);
        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $directSend = $this->getContainer()->get(DirectSendService::class);
        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $lobbyUSerRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
        $urlLeave = $urlGenerator->generate('lobby_participants_leave', ['userUid' => 'test']);
        $crawler = $client->request('GET', $urlLeave);
        self::assertEquals('{"error":true}', $client->getResponse()->getContent());
        self::assertNull($lobbyUSerRepo->findOneBy(['user' => $user2, 'room' => $room]));
        $url = $urlGenerator->generate('lobby_participants_wait', ['roomUid' => $room->getUidReal(), 'userUid' => $user2->getUid()]);
        $crawler = $client->request('GET', $url);
        $lobbyUser = $lobbyUSerRepo->findOneBy(['user' => $user2, 'room' => $room]);
        self::assertNotNull($lobbyUser);
        $urlLeave = $urlGenerator->generate('lobby_participants_leave', ['userUid' => $lobbyUser->getUid()]);
        $this->assertStringContainsString('href="' . $urlLeave, $client->getResponse()->getContent());
        $crawler = $client->request('GET', $urlLeave);
        self::assertEquals('{"error":false}', $client->getResponse()->getContent());
        self::assertNull($lobbyUSerRepo->findOneBy(['user' => $user2, 'room' => $room]));
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.async');
        $this->assertCount(0, $transport->get());
    }

    public function testRefresh(): void
    {
        $client = static::createClient();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'This is a room with Lobby']);
        $moderator = $room->getModerator();
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($moderator);
        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $directSend = $this->getContainer()->get(DirectSendService::class);
        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $lobbyUserRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
        $urlLeave = $urlGenerator->generate('lobby_participants_leave', ['userUid' => 'test']);
        $crawler = $client->request('GET', $urlLeave);
        self::assertEquals('{"error":true}', $client->getResponse()->getContent());
        self::assertNull($lobbyUserRepo->findOneBy(['user' => $user2, 'room' => $room]));
        $url = $urlGenerator->generate('lobby_participants_wait', ['roomUid' => $room->getUidReal(), 'userUid' => $user2->getUid()]);
        $crawler = $client->request('GET', $url);
        $lobbyUser = $lobbyUserRepo->findOneBy(['user' => $user2, 'room' => $room]);
        self::assertNotNull($lobbyUser);
        $urlLeave = '/lobby/browser/leave/participants/'.$lobbyUser->getUid();
        $crawler = $client->request('GET', $urlLeave);
        self::assertEquals('{"error":false}', $client->getResponse()->getContent());
        self::assertNotNull($lobbyUserRepo->findOneBy(['user' => $user2, 'room' => $room]));
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.async');
        sleep(4);
        $this->assertCount(1, $transport->get());
    }

    public function testHealthcheck(): void
    {
        $client = static::createClient();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'This is a room with Lobby']);
        $moderator = $room->getModerator();
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($moderator);
        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $directSend = $this->getContainer()->get(DirectSendService::class);
        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $lobbyUSerRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
        $url = $urlGenerator->generate('lobby_participants_wait', ['roomUid' => $room->getUidReal(), 'userUid' => $user2->getUid()]);
        $crawler = $client->request('GET', $url);
        $lobbyUser = $lobbyUSerRepo->findOneBy(['user' => $user2, 'room' => $room]);
        self::assertNotNull($lobbyUser);
        $lobbyUser = $lobbyUSerRepo->findOneBy(['user' => $user2, 'room' => $room]);
        self::assertNotNull($lobbyUser);
        $urlHealthCheck = $urlGenerator->generate('lobby_participants_healthCheck', ['userUid' => $lobbyUser->getUid()]);
        $crawler = $client->request('GET', $urlHealthCheck);
        self::assertEquals('{"error":false}', $client->getResponse()->getContent());
        $urlLeave = $urlGenerator->generate('lobby_participants_leave', ['userUid' => $lobbyUser->getUid()]);
        $crawler = $client->request('GET', $urlLeave);
        $urlHealthCheck = $urlGenerator->generate('lobby_participants_healthCheck', ['userUid' => $lobbyUser->getUid()]);
        $crawler = $client->request('GET', $urlHealthCheck);
        self::assertEquals('{"error":true}', $client->getResponse()->getContent());
    }
}
