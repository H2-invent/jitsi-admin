<?php

namespace App\Tests;

use App\Entity\LobbyWaitungUser;
use App\Repository\LobbyWaitungUserRepository;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\Lobby\DirectSendService;
use App\Service\JoinUrlGeneratorService;
use App\Service\RoomService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LobbyParticipantsControllerTest extends WebTestCase
{

    public function testLobby(): void
    {
        $client = static::createClient();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'This is a room with Lobby'));
        $moderator = $room->getModerator();
        $user2 = $userRepo->findOneBy(array('email' => 'test@local2.de'));
        $client->loginUser($moderator);
        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $directSend = $this->getContainer()->get(DirectSendService::class);
        $hub = new MockHub('http://localhost:3000/.well-known/mercure', new StaticTokenProvider('test'), function (Update $update): string {
                 return 'id';
        });
        $directSend->setMercurePublisher($hub);
        $lobbyUSerRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        self::assertNull($lobbyUSerRepo->findOneBy(array('user'=>$user2, 'room'=>$room)));
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
        $url = $urlGenerator->generate('lobby_participants_wait',array('roomUid'=>$room->getUidReal(),'userUid'=>$user2->getUid()));
        $crawler = $client->request('GET', $url);
        self::assertNotNull($lobbyUSerRepo->findOneBy(array('user'=>$user2, 'room'=>$room)));

        $this->assertEquals(
            1,
            $crawler->filter('.overlay:contains("Bitte warten Sie. Der Moderator wurde informiert und lässt Sie eintreten.")')->count()
        );
    }

    public function testRenew(): void
    {
        $client = static::createClient();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'This is a room with Lobby'));
        $moderator = $room->getModerator();
        $user2 = $userRepo->findOneBy(array('email' => 'test@local2.de'));
        $client->loginUser($moderator);
        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $directSend = $this->getContainer()->get(DirectSendService::class);
        $hub = new MockHub('http://localhost:3000/.well-known/mercure', new StaticTokenProvider('test'), function (Update $update): string {
            return 'id';
        });
        $directSend->setMercurePublisher($hub);
        $lobbyUSerRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
        $urlRenew = $urlGenerator->generate('lobby_participants_renew',array('roomUid'=>$room->getUidReal(),'userUid'=>$user2->getUid()));
        $crawler = $client->request('GET', $urlRenew);
        self::assertEquals('{"error":true,"message":"Fehler"}', $client->getResponse()->getContent());
        self::assertNull($lobbyUSerRepo->findOneBy(array('user'=>$user2, 'room'=>$room)));
        $url = $urlGenerator->generate('lobby_participants_wait',array('roomUid'=>$room->getUidReal(),'userUid'=>$user2->getUid()));
        $crawler = $client->request('GET', $url);
        self::assertNotNull($lobbyUSerRepo->findOneBy(array('user'=>$user2, 'room'=>$room)));
        $crawler = $client->request('GET', $urlRenew);
        self::assertEquals('{"error":false,"message":"Sie haben Ihren Beitritt erfolgreich angefordert.","color":"success"}', $client->getResponse()->getContent());
    }
    public function testLeave(): void
    {
        $client = static::createClient();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'This is a room with Lobby'));
        $moderator = $room->getModerator();
        $user2 = $userRepo->findOneBy(array('email' => 'test@local2.de'));
        $client->loginUser($moderator);
        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $directSend = $this->getContainer()->get(DirectSendService::class);
        $hub = new MockHub('http://localhost:3000/.well-known/mercure', new StaticTokenProvider('test'), function (Update $update): string {
            return 'id';
        });
        $directSend->setMercurePublisher($hub);
        $lobbyUSerRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
        $urlLeave = $urlGenerator->generate('lobby_participants_leave',array('roomUid'=>$room->getUidReal(),'userUid'=>$user2->getUid()));
        $crawler = $client->request('GET', $urlLeave);
        self::assertEquals('{"error":true}', $client->getResponse()->getContent());
        self::assertNull($lobbyUSerRepo->findOneBy(array('user'=>$user2, 'room'=>$room)));
        $url = $urlGenerator->generate('lobby_participants_wait',array('roomUid'=>$room->getUidReal(),'userUid'=>$user2->getUid()));
        $crawler = $client->request('GET', $url);
        self::assertNotNull($lobbyUSerRepo->findOneBy(array('user'=>$user2, 'room'=>$room)));
        $urlLeave = $urlGenerator->generate('lobby_participants_leave',array('roomUid'=>$room->getUidReal(),'userUid'=>$user2->getUid()));
        $crawler = $client->request('GET', $urlLeave);
        self::assertEquals('{"error":false}', $client->getResponse()->getContent());
        self::assertNull($lobbyUSerRepo->findOneBy(array('user'=>$user2, 'room'=>$room)));
       }
}
