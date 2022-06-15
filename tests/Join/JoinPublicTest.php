<?php

namespace App\Tests\Join;

use App\Repository\RoomsRepository;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use App\Service\RoomService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class JoinPublicTest extends WebTestCase
{
    public function testNoServer(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3','Konferenz beitreten');
        $this->assertStringNotContainsString('https://privacy.dev',$client->getResponse()->getContent());
        $this->assertStringNotContainsString('https://test.img',$client->getResponse()->getContent());

    }

    public function testWithLicenseServer(): void
    {
        $client = static::createClient();
        $serverRepo = $this->getContainer()->get(ServerRepository::class);
        $server = $serverRepo->findOneBy(array('url'=>'meet.jit.si2'));
        $crawler = $client->request('GET', '/join/'.$server->getSlug());
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('https://test.img',$client->getResponse()->getContent());
    }

    public function testWithServer(): void
    {
        $client = static::createClient();
        $serverRepo = $this->getContainer()->get(ServerRepository::class);
        $server = $serverRepo->findOneBy(array('url'=>'meet.jit.si'));
        $crawler = $client->request('GET', '/join/'.$server->getSlug());
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('https://privacy.dev',$client->getResponse()->getContent());
    }

    public function testJoinConference_Open_Correctuser_Userisloginuser_Correctroomnumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3','Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Im Browser beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local2.de'));
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 1'));
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/room/join/b/'.$room->getId()));
        $this->assertEquals(302,$client->getResponse()->getStatusCode());
        $buttonCrawlerNode = $crawler->selectButton('Mit der Elektron-App beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $urlGen = self::getContainer()->get(RoomService::class);
        $url = $urlGen->joinUrl('a',$room,'Test User 123',false);
        $this->assertTrue($client->getResponse()->isRedirect('/room/join/a/'.$room->getId()));
        $this->assertEquals(302,$client->getResponse()->getStatusCode());
    }
    public function testJoin_ConferenceOpen_CorrectuserUser_isnoLoginuser_Correctroomnumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3','Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Im Browser beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local3.de'));
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 1'));
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertSelectorTextContains('title','TestMeeting: 1');
        $this->assertStringContainsString('<title>TestMeeting: 1</title>',$client->getResponse()->getContent());
        $this->assertEquals(200,$client->getResponse()->getStatusCode());
        $buttonCrawlerNode = $crawler->selectButton('Mit der Elektron-App beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $urlGen = self::getContainer()->get(RoomService::class);
        $url = $urlGen->joinUrl('a',$room,'Test User 123',false);
        $this->assertTrue($client->getResponse()->isRedirect($url));
    }
    public function testJoin_ConferenceClosed_Correctuser_Userisnologinuser_Correctroomnumber(): void
    {
        $client = static::createClient();
        $session = new Session(new MockArraySessionStorage());
        $app['session.storage'] = new MockArraySessionStorage();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3','Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Im Browser beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local3.de'));
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 19'));
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $res = 'Der Beitritt ist nur von '.($room->getStart())->modify('-30min')->format('d.m.Y H:i T').' bis '.($room->getEnddate())->format('d.m.Y H:i T').' möglich';
        $this->assertSelectorTextContains('.innerOnce',$res);
    }
    public function testJoin_ConferenceClosed_Correctuser_Userisloginuser_Correctroomnumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3','Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Im Browser beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local2.de'));
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 19'));
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/room/join/b/'.$room->getId()));

    }
    public function testJoin_ConferenceClosed_CorrectuserUser_loginuser_Userismoderator_Correctroomnumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3','Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Im Browser beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local.de'));
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 19'));
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/room/join/b/'.$room->getId()));

        $buttonCrawlerNode = $crawler->selectButton('Mit der Elektron-App beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/room/join/a/'.$room->getId()));
    }
    public function testJoin_ConferenceOpen_CorrectuserUser_loginuser_Userismoderator_Correctroomnumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3','Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Im Browser beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local.de'));
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 1'));
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/room/join/b/'.$room->getId()));
        $buttonCrawlerNode = $crawler->selectButton('Mit der Elektron-App beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/room/join/a/'.$room->getId()));
    }
    public function testJoin_Conference_Notcorrectuser_User(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3','Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Im Browser beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local4.de'));
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 1'));
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertSelectorTextContains('.innerOnce','Fehler, bitte kontrollieren Sie Ihre Daten.');
    }
    public function testJoin_Conference_NotcorrectRoom(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3','Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Im Browser beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local4.de'));
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 1'));
        $form['join_view[uid]'] = 'wrongId123';
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertSelectorTextContains('.snackbar','Fehler, bitte kontrollieren Sie Ihre Daten.');
    }
    public function testJoin_Conference_UserDoesNotexist(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3','Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Im Browser beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local6.de'));
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 1'));
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] ='usernotexits@local6.de';
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertSelectorTextContains('.snackbar','Fehler, bitte kontrollieren Sie Ihre Daten.');
    }
    public function testJoin_Conference_fixedroom_Correctuser_Userloginuser_Userismoderator_Correctroomnumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3','Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Im Browser beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local.de'));
        $room = $roomRepo->findOneBy(array('name'=>'This is a fixed room'));
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/room/join/b/'.$room->getId()));
        $buttonCrawlerNode = $crawler->selectButton('Mit der Elektron-App beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/room/join/a/'.$room->getId()));
    }
    public function testJoinConference_fixedroom_CorrectuserUser_loginuser_Correctroomnumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3','Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Im Browser beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local2.de'));
        $room = $roomRepo->findOneBy(array('name'=>'This is a fixed room'));
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect('/room/join/b/'.$room->getId()));
        $buttonCrawlerNode = $crawler->selectButton('Mit der Elektron-App beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/room/join/a/'.$room->getId()));
    }
    public function testJoinConference_fixedroom_Correctuser_Usernologinuser_Correctroomnumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3','Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Im Browser beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local3.de'));
        $room = $roomRepo->findOneBy(array('name'=>'This is a fixed room'));
        $roomService = $this->getContainer()->get(RoomService::class);
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertSelectorTextContains('title','This is a fixed room');
        $this->assertStringContainsString('<title>This is a fixed room</title>',$client->getResponse()->getContent());
        $this->assertStringContainsString('https://'.$room->getServer()->getUrl().'/external_api.js',$client->getResponse()->getContent());
        $this->assertStringContainsString("roomName: '".$room->getUid()."',",$client->getResponse()->getContent());
        $this->assertStringContainsString("jwt: '".$roomService->generateJwt($room,$user,'Test User 123')."',",$client->getResponse()->getContent());
        $buttonCrawlerNode = $crawler->selectButton('Mit der Elektron-App beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect($roomService->join($room,$user,'a','Test User 123')));
    }
    public function testJoinConference_fixedroom_Nocorrectuser_Usernologinuser_Correctroomnumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3','Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Im Browser beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local4.de'));
        $room = $roomRepo->findOneBy(array('name'=>'This is a fixed room'));
        $roomService = $this->getContainer()->get(RoomService::class);
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertSelectorTextContains('.snackbar','Fehler, bitte kontrollieren Sie Ihre Daten.');
    }
}
