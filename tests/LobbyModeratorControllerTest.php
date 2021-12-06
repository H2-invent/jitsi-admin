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

class LobbyModeratorControllerTest extends WebTestCase
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

            self::assertEquals('{"type":"refresh","reloadUrl":"\/rooms\/testMe #testId"}', $update->getData());
            self::assertEquals(['test/test/numberofUser'], $update->getTopics());
            return 'id';
        });
        $directSend->setMercurePublisher($hub);
        $crawler = $client->request('GET', '/room/lobby/moderator/a/' . $room->getUidReal());
        $this->assertEquals(
            0,
            $crawler->filter('.participantsName:contains("User, Test, test@local2.de")')->count()
        );
        $this->assertResponseIsSuccessful();
        $lobbyUser = new LobbyWaitungUser();
        $lobbyUser->setRoom($room);
        $lobbyUser->setCreatedAt(new \DateTime());
        $lobbyUser->setUser($user2);
        $lobbyUser->setType('a');
        $lobbyUser->setUid('lkdsjhflkjlkdsjflkjdslkjflkjdslkjf');
        $em->persist($lobbyUser);
        $em->flush();
        $crawler = $client->request('GET', '/room/lobby/moderator/a/' . $room->getUidReal());
        $this->assertEquals(
            1,
            $crawler->filter('.participantsName:contains("User, Test, test@local2.de")')->count()
        );
        $this->assertResponseIsSuccessful();
        $client->loginUser($user2);
        $crawler = $client->request('GET', '/room/lobby/moderator/a/' . $room->getUidReal());
        self::assertResponseRedirects('/room/dashboard?snack=Fehler&color=danger');
    }

    public function testAccept(): void
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
        $lobbyUser = new LobbyWaitungUser();
        $lobbyUser->setRoom($room);
        $lobbyUser->setType('a');
        $lobbyUser->setCreatedAt(new \DateTime());
        $lobbyUser->setUser($user2);
        $lobbyUser->setUid('lkdsjhflkjlkdsjflkjdslkjflkjdslkjf');
        $em->persist($lobbyUser);
        $em->flush();
        $url = self::getContainer()->get(UrlGeneratorInterface::class);
        $acceptUrl = $url->generate('lobby_moderator_accept', array('wUid' => $lobbyUser->getUid()));
        self::assertNotNull($lobbyUSerRepo->findOneBy(array('uid' => 'lkdsjhflkjlkdsjflkjdslkjflkjdslkjf')));
        $crawler = $client->request('GET', $acceptUrl);
        self::assertEquals('{"error":false,"message":"Sie haben den Teilnehmer erfolgreich der Konferenz hinzugef\u00fct","color":"success"}', $client->getResponse()->getContent());
        self::assertNull($lobbyUSerRepo->findOneBy(array('uid' => 'lkdsjhflkjlkdsjflkjdslkjflkjdslkjf')));
        $crawler = $client->request('GET', $acceptUrl);
        self::assertEquals('{"error":false,"message":"Fehler, bitte laden Sie die Seite neu","color":"danger"}', $client->getResponse()->getContent());
        self::assertNull($lobbyUSerRepo->findOneBy(array('uid' => 'lkdsjhflkjlkdsjflkjdslkjflkjdslkjf')));
        $this->assertResponseIsSuccessful();
        $client->loginUser($user2);
        $crawler = $client->request('GET', $acceptUrl);
        self::assertEquals('{"error":false,"message":"Fehler, bitte laden Sie die Seite neu","color":"danger"}', $client->getResponse()->getContent());
    }

    public function testDecline(): void
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
        $lobbyUser = new LobbyWaitungUser();
        $lobbyUser->setRoom($room);
        $lobbyUser->setType('a');
        $lobbyUser->setCreatedAt(new \DateTime());
        $lobbyUser->setUser($user2);
        $lobbyUser->setUid('lkdsjhflkjlkdsjflkjdslkjflkjdslkjf');
        $em->persist($lobbyUser);
        $em->flush();
        $url = self::getContainer()->get(UrlGeneratorInterface::class);
        $acceptUrl = $url->generate('lobby_moderator_decline', array('wUid' => $lobbyUser->getUid()));
        self::assertNotNull($lobbyUSerRepo->findOneBy(array('uid' => 'lkdsjhflkjlkdsjflkjdslkjflkjdslkjf')));
        $crawler = $client->request('GET', $acceptUrl);
        self::assertEquals('{"error":false,"message":"Dieser Teilnehmer hat keinen Zutritt zu der Konferenz","color":"success"}', $client->getResponse()->getContent());
        self::assertNull($lobbyUSerRepo->findOneBy(array('uid' => 'lkdsjhflkjlkdsjflkjdslkjflkjdslkjf')));
        $crawler = $client->request('GET', $acceptUrl);
        self::assertEquals('{"error":false,"message":"Fehler, bitte laden Sie die Seite neu","color":"danger"}', $client->getResponse()->getContent());
        self::assertNull($lobbyUSerRepo->findOneBy(array('uid' => 'lkdsjhflkjlkdsjflkjdslkjflkjdslkjf')));
        $this->assertResponseIsSuccessful();
        $client->loginUser($user2);
        $crawler = $client->request('GET', $acceptUrl);
        self::assertEquals('{"error":false,"message":"Fehler, bitte laden Sie die Seite neu","color":"danger"}', $client->getResponse()->getContent());
    }

    public function testStartConference(): void
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
        $url = self::getContainer()->get(UrlGeneratorInterface::class);
        $urlGenerator = self::getContainer()->get(RoomService::class);
        $startUrl = $url->generate('lobby_moderator_start', array('room' => $room->getUidReal(), 't' => 'a'));
        $crawler = $client->request('GET', $startUrl);
        $paramterBag = self::getContainer()->get(ParameterBagInterface::class);
        self::assertResponseRedirects($urlGenerator->joinUrl('a', $room, $moderator->getFormatedName($paramterBag->get('laf_showNameInConference')),true));
        $startUrl = $url->generate('lobby_moderator_start', array('room' => $room->getUidReal(), 't' => 'b'));
        $crawler = $client->request('GET', $startUrl);
        $paramterBag = self::getContainer()->get(ParameterBagInterface::class);
        self::assertResponseRedirects($urlGenerator->joinUrl('b', $room, $moderator->getFormatedName($paramterBag->get('laf_showNameInConference')),true));
        $client->loginUser($user2);
        $crawler = $client->request('GET', $startUrl);
        self::assertResponseRedirects('/room/dashboard?snack=Fehler&color=danger');
    }
    public function testAcceptAll(): void
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
        $lobbyUser = new LobbyWaitungUser();
        $lobbyUser->setType('a');
        $lobbyUser->setRoom($room);
        $lobbyUser->setCreatedAt(new \DateTime());
        $lobbyUser->setUser($user2);
        $lobbyUser->setUid('lkdsjhflkjlkdsjflkjdslkjflkjdslkjf');
        $em->persist($lobbyUser);
        $lobbyUser2 = new LobbyWaitungUser();
        $lobbyUser2->setType('a');
        $lobbyUser2->setRoom($room);
        $lobbyUser2->setCreatedAt(new \DateTime());
        $lobbyUser2->setUser($moderator);
        $lobbyUser2->setUid('lkdsjhflkjlkdsfghhgfjflkjdslkjflkjdslkjf');
        $em->persist($lobbyUser2);
        $em->flush();

        $url = self::getContainer()->get(UrlGeneratorInterface::class);
        $crawler = $client->request('GET',$url->generate('lobby_moderator',array('uid'=>$room->getUidReal())));
        self::assertEquals(2,$crawler->filter('.waitingUserCard')->count());
        $client->loginUser($user2);
        $crawler = $client->request('GET',$url->generate('lobby_moderator_accept_all',array('roomId'=>$room->getUidReal())));
        self::assertEquals('{"error":false,"message":"Fehler, bitte laden Sie die Seite neu","color":"danger"}',$client->getResponse()->getContent());
        $client->loginUser($moderator);
        $crawler = $client->request('GET',$url->generate('lobby_moderator_accept_all',array('roomId'=>$room->getUidReal())));
        self::assertEquals('{"error":false,"message":"Alle Teilnehmenden wurden erfolgreich zur Konferenz zugelassen","color":"success"}',$client->getResponse()->getContent());
        $crawler = $client->request('GET',$url->generate('lobby_moderator',array('uid'=>$room->getUidReal())));
        self::assertEquals(0,$crawler->filter('.waitingUserCard')->count());
    }
}
