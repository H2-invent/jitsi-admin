<?php

namespace App\Tests\Join;

use App\Entity\LobbyWaitungUser;
use App\Repository\LobbyWaitungUserRepository;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\PermissionChangeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class JoinPublicLobbyTest extends WebTestCase
{
    public function testJoin_ConferencewithLobbyAcceptWaitingUser(): void
    {
        $client = static::createClient();
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $room->setLobby(true);
        $room->setPersistantRoom(true);
        $room->setStart(null);
        $room->setEnddate(null);
        $room->setTotalOpenRooms(true);
        $manager->persist($room);
        $manager->flush();

        $wu = new LobbyWaitungUser();
        $wu->setShowName('test Me Lobby WaitingUser');
        $wu->setCreatedAt(new \DateTime())
            ->setRoom($room)
            ->setType('b')
            ->setUid('lksdjflkdsjf');
        $manager->persist($wu);
        $manager->flush();
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local3.de']);
        $permissionService = self::getContainer()->get(PermissionChangeService::class);
        $permissionService->toggleLobbyModerator($room->getModerator(), $user, $room);


        $crawler = $client->request('GET', '/room/lobby/moderator/b/' . $room->getUidReal());
        self::assertEquals(500, $client->getResponse()->getStatusCode());


        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.joinPageHeader', 'Konferenz beitreten');


        $buttonCrawlerNode = $crawler->selectButton('Beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        self::assertSelectorTextContains('.joinPageHeader', 'TestMeeting: 1');
        self::assertEquals($user->getId(), $client->getRequest()->getSession()->get('userId'));
        $client->request('GET', '/room/lobby/moderator/b/' . $room->getUidReal());
        self::assertResponseIsSuccessful();

        $waitingUSerRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $waitingUSer = $waitingUSerRepo->findOneBy(['uid' => 'lksdjflkdsjf']);
        self::assertEquals($wu->getId(), $waitingUSer->getId());

        $client->request('GET', '/room/lobby/accept/lksdjflkdsjf');
        self::assertResponseIsSuccessful();
        $waitingUSer = $waitingUSerRepo->findOneBy(['uid' => 'lksdjflkdsjf']);
        self::assertNull($waitingUSer);
    }
    public function testJoin_ConferencewithLobbyDeclineWaitingUser(): void
    {
        $client = static::createClient();
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $room->setLobby(true);
        $room->setPersistantRoom(true);
        $room->setStart(null);
        $room->setEnddate(null);
        $room->setTotalOpenRooms(true);
        $manager->persist($room);
        $manager->flush();

        $wu = new LobbyWaitungUser();
        $wu->setShowName('test Me Lobby WaitingUser');
        $wu->setCreatedAt(new \DateTime())
            ->setRoom($room)
            ->setType('b')
            ->setUid('lksdjflkdsjf');
        $manager->persist($wu);
        $manager->flush();
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local3.de']);
        $permissionService = self::getContainer()->get(PermissionChangeService::class);
        $permissionService->toggleLobbyModerator($room->getModerator(), $user, $room);


        $crawler = $client->request('GET', '/room/lobby/moderator/b/' . $room->getUidReal());
        self::assertEquals(500, $client->getResponse()->getStatusCode());


        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.joinPageHeader', 'Konferenz beitreten');


        $buttonCrawlerNode = $crawler->selectButton('Beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        self::assertSelectorTextContains('.joinPageHeader', 'TestMeeting: 1');
        self::assertEquals($user->getId(), $client->getRequest()->getSession()->get('userId'));
        $client->request('GET', '/room/lobby/moderator/b/' . $room->getUidReal());
        self::assertResponseIsSuccessful();

        $waitingUSerRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $waitingUSer = $waitingUSerRepo->findOneBy(['uid' => 'lksdjflkdsjf']);
        self::assertEquals($wu->getId(), $waitingUSer->getId());

        $client->request('GET', '/room/lobby/decline/lksdjflkdsjf');
        self::assertResponseIsSuccessful();
        $waitingUSer = $waitingUSerRepo->findOneBy(['uid' => 'lksdjflkdsjf']);
        self::assertNull($waitingUSer);
    }

    public function testJoin_ConferencewithLobbyStartConference(): void
    {
        $client = static::createClient();
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $room->setLobby(true);
        $room->setPersistantRoom(true);
        $room->setStart(null);
        $room->setEnddate(null);
        $room->setTotalOpenRooms(true);
        $manager->persist($room);
        $manager->flush();
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local3.de']);
        $permissionService = self::getContainer()->get(PermissionChangeService::class);
        $permissionService->toggleLobbyModerator($room->getModerator(), $user, $room);


        $crawler = $client->request('GET', '/room/lobby/moderator/b/' . $room->getUidReal());
        self::assertEquals(500, $client->getResponse()->getStatusCode());


        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.joinPageHeader', 'Konferenz beitreten');


        $buttonCrawlerNode = $crawler->selectButton('Beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        self::assertSelectorTextContains('.joinPageHeader', 'TestMeeting: 1');
        self::assertEquals($user->getId(), $client->getRequest()->getSession()->get('userId'));
        $client->request('GET', '/room/lobby/moderator/b/' . $room->getUidReal());
        self::assertResponseIsSuccessful();


        $client->request('GET', '/room/lobby/start/moderator/b/' . $room->getUidReal());
        self::assertEquals(302, $client->getResponse()->getStatusCode());

        $client->request('GET', '/room/lobby/acceptAll/' . $room->getUidReal());
        self::assertEquals(200, $client->getResponse()->getStatusCode());

        $client->request('GET', '/lobby/moderator/endMeeting/' . $room->getUidReal());
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
