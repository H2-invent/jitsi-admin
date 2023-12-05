<?php

namespace App\Tests\Join;

use App\Repository\RoomsRepository;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use App\Service\RoomService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class JoinPublicTest extends WebTestCase
{
    public function testNoServer(): void
    {
        $client = static::createClient();
        $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.joinPageHeader', 'Konferenz beitreten');
        $this->assertStringNotContainsString('https://privacy.dev', $client->getResponse()->getContent());
        $this->assertStringNotContainsString('https://test.img', $client->getResponse()->getContent());
    }

    public function testWithLicenseServer(): void
    {
        $client = static::createClient();
        $serverRepo = $this->getContainer()->get(ServerRepository::class);
        $server = $serverRepo->findOneBy(['url' => 'meet.jit.si2']);
        $client->request('GET', '/join/' . $server->getSlug());
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('https://test.img', $client->getResponse()->getContent());
    }

    public function testWithServer(): void
    {
        $client = static::createClient();
        $serverRepo = $this->getContainer()->get(ServerRepository::class);
        $server = $serverRepo->findOneBy(['url' => 'meet.jit.si']);
        $client->request('GET', '/join/' . $server->getSlug());
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('https://privacy.dev', $client->getResponse()->getContent());
    }

    public function testJoinConferenceOpenCorrectUserUserIsLoginUserCorrectRoomNumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.joinPageHeader', 'Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/room/join/b/' . $room->getId()));
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $buttonCrawlerNode = $crawler->selectButton('Mit der Elektron-App beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $urlGen = self::getContainer()->get(RoomService::class);
        $url = $urlGen->joinUrl('a', $room, 'Test User 123', false);
        $this->assertTrue($client->getResponse()->isRedirect('/room/join/a/' . $room->getId()));
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testJoinConferenceOpenCorrectuserUserIsNoLoginUserCorrectRoomNumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.joinPageHeader', 'Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local3.de']);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertSelectorTextContains('title', 'TestMeeting: 1');
        $this->assertStringContainsString('<title>TestMeeting: 1</title>', $client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $buttonCrawlerNode = $crawler->selectButton('Mit der Elektron-App beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $urlGen = self::getContainer()->get(RoomService::class);
        $url = $urlGen->joinUrl('a', $room, 'Test User 123', false);
        $this->assertTrue($client->getResponse()->isRedirect($url));
    }

    public function testJoinConferenceClosedCorrectUserUserIsNotLoginUserCorrectRoomNumber(): void
    {
        $client = static::createClient();
        $session = new Session(new MockArraySessionStorage());
        $app['session.storage'] = new MockArraySessionStorage();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.joinPageHeader', 'Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local3.de']);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $res = 'Der Beitritt ist nur von ' .
            ($room->getStart())->modify('-30min')->format('d.m.Y H:i T') .
            ' bis ' .
            ($room->getEnddate())->format('d.m.Y H:i T') .
            ' möglich';
        $this->assertSelectorTextContains('.innerOnce', $res);
    }

    public function testJoinConferenceClosedCorrectUserUserIsLoginUserCorrectRoomNumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.joinPageHeader', 'Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/room/join/b/' . $room->getId()));
    }

    public function testJoinConferenceClosedCorrectUserUserIsLoginUserUserIsModeratorCorrectRoomNumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.joinPageHeader', 'Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/room/join/b/' . $room->getId()));

        $buttonCrawlerNode = $crawler->selectButton('Mit der Elektron-App beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/room/join/a/' . $room->getId()));
    }

    public function testJoinConferenceOpenCorrectUserUserLoginUserUserIsModeratorCorrectRoomNumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.joinPageHeader', 'Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/room/join/b/' . $room->getId()));
        $buttonCrawlerNode = $crawler->selectButton('Mit der Elektron-App beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/room/join/a/' . $room->getId()));
    }

    public function testJoinConferenceNotCorrectUser(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.joinPageHeader', 'Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local4.de']);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertSelectorTextContains(
            '.innerOnce',
            'Fehler: Ihre E-Mail-Adresse ist nicht in der Teilnehmendenliste! Bitte kontaktieren Sie den Moderator, damit dieser Sie zu der Konferenz einlädt.'
        );
    }

    public function testJoinConferenceNotCorrectRoom(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.joinPageHeader', 'Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local4.de']);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $form['join_view[uid]'] = 'wrongId123';
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertSelectorTextContains(
            '.snackbar',
            'Fehler: Ihre E-Mail-Adresse ist nicht in der Teilnehmendenliste! Bitte kontaktieren Sie den Moderator, damit dieser Sie zu der Konferenz einlädt.'
        );
    }

    public function testJoinConferenceUserDoesNotExist(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.joinPageHeader', 'Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local6.de']);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = 'usernotexits@local6.de';
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertSelectorTextContains(
            '.snackbar',
            'Fehler: Ihre E-Mail-Adresse ist nicht in der Teilnehmendenliste! Bitte kontaktieren Sie den Moderator, damit dieser Sie zu der Konferenz einlädt.'
        );
    }

    public function testJoinConferenceFixedRoomCorrectUserUserLoginUserUserIsModeratorCorrectRoomNumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.joinPageHeader', 'Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $room = $roomRepo->findOneBy(['name' => 'This is a fixed room']);
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/room/join/b/' . $room->getId()));
        $buttonCrawlerNode = $crawler->selectButton('Mit der Elektron-App beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/room/join/a/' . $room->getId()));
    }

    public function testJoinConferenceFixedRoomCorrectUserUserLoginUserCorrectRoomNumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.joinPageHeader', 'Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $room = $roomRepo->findOneBy(['name' => 'This is a fixed room']);
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect('/room/join/b/' . $room->getId()));
        $buttonCrawlerNode = $crawler->selectButton('Mit der Elektron-App beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/room/join/a/' . $room->getId()));
    }

    public function testJoinConferenceFixedRoomCorrectUserUserNoLoginUserCorrectRoomNumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.joinPageHeader', 'Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local3.de']);
        $room = $roomRepo->findOneBy(['name' => 'This is a fixed room']);
        $roomService = $this->getContainer()->get(RoomService::class);
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertSelectorTextContains('title', 'This is a fixed room');
        $this->assertStringContainsString('<title>This is a fixed room</title>', $client->getResponse()->getContent());
        $this->assertStringContainsString(
            'https://' . $room->getServer()->getUrl() . '/external_api.js', $client->getResponse()->getContent()
        );
         $this->assertStringContainsString("roomName: '". $room->getUid() . "',", $client->getResponse()->getContent());
        $this->assertStringContainsString(
            "jwt: '" . $roomService->generateJwt($room, $user, 'Test User 123') . "',", $client->getResponse()->getContent()
        );
        $buttonCrawlerNode = $crawler->selectButton('Mit der Elektron-App beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect($roomService->join($room, $user, 'a', 'Test User 123')));
    }
    public function testJoinConferenceFixedRoomCorrectUserUserNoLoginUserCorrectRoomNumberWithPrefix(): void
    {
        $client = static::createClient();
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.joinPageHeader', 'Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local3.de']);
        $room = $roomRepo->findOneBy(['name' => 'This is a fixed room']);
        $room->getServer()->setPrefixRoomUidWithHash(true);
        $manager->persist($room);
        $manager->flush();
        $roomService = $this->getContainer()->get(RoomService::class);
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertSelectorTextContains('title', 'This is a fixed room');
        $this->assertStringContainsString('<title>This is a fixed room</title>', $client->getResponse()->getContent());
        $this->assertStringContainsString(
            'https://' . $room->getServer()->getUrl() . '/external_api.js', $client->getResponse()->getContent()
        );
        $this->assertStringContainsString("roomName: 'a38d63dc4ce308b7a5a296d4f3a42c29/". $room->getUid() . "',", $client->getResponse()->getContent());
        $this->assertStringContainsString(
            "jwt: '" . $roomService->generateJwt($room, $user, 'Test User 123') . "',", $client->getResponse()->getContent()
        );
        $buttonCrawlerNode = $crawler->selectButton('Mit der Elektron-App beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect($roomService->join($room, $user, 'a', 'Test User 123')));
    }

    public function testJoinConferenceFixedRoomNoCorrectUserUserNoLoginUserCorrectRoomNumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.joinPageHeader', 'Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local4.de']);
        $room = $roomRepo->findOneBy(['name' => 'This is a fixed room']);
        $roomService = $this->getContainer()->get(RoomService::class);
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertSelectorTextContains(
            '.snackbar',
            'Fehler: Ihre E-Mail-Adresse ist nicht in der Teilnehmendenliste! Bitte kontaktieren Sie den Moderator, damit dieser Sie zu der Konferenz einlädt.'
        );
    }
}
