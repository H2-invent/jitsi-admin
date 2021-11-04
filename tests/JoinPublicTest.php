<?php

namespace App\Tests;

use App\Repository\RoomsRepository;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use App\Service\RoomService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class JoinPublicTest extends WebTestCase
{
    public function testNoServer(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1','Konferenz beitreten');
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

    public function testJoinConferenceOpenCorrectuserUserisloginuserCorrectroomnumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1','Konferenz beitreten');
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
        $buttonCrawlerNode = $crawler->selectButton('Mit der Elektron-App beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/room/join/a/'.$room->getId()));
    }
    public function testJoinConferenceOpenCorrectuserUserisnoLoginuserCorrectroomnumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1','Konferenz beitreten');
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
        $roomService = $this->getContainer()->get(RoomService::class);
        $this->assertTrue($client->getResponse()->isRedirect($roomService->join($room,$user,'b','Test User 123')));
        $buttonCrawlerNode = $crawler->selectButton('Mit der Elektron-App beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect($roomService->join($room,$user,'a','Test User 123')));
    }
    public function testJoinConferenceClosedCorrectuserUserisnologinuserCorrectroomnumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1','Konferenz beitreten');
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
        $res = 'Der Beitritt ist nur von '.($room->getStart())->modify('-30min')->format('d.m.Y H:i').' bis '.($room->getEnddate())->format('d.m.Y H:i').' möglich';
        $this->assertSelectorTextContains('#snackbar',$res);
    }
    public function testJoinConferenceClosedCorrectuserUserisloginuserCorrectroomnumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1','Konferenz beitreten');
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
        $res = 'Der Beitritt ist nur von '.($room->getStart())->modify('-30min')->format('d.m.Y H:i').' bis '.($room->getEnddate())->format('d.m.Y H:i').' möglich';
        $this->assertSelectorTextContains('#snackbar',$res);
    }
    public function testJoinConferenceClosedCorrectuserUserloginuserUserismoderatorCorrectroomnumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1','Konferenz beitreten');
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
    public function testJoinConferenceOpenCorrectuserUserloginuserUserismoderatorCorrectroomnumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1','Konferenz beitreten');
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
    public function testJoinConferenceNotcorrectuserUser(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1','Konferenz beitreten');
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
        $this->assertSelectorTextContains('#snackbar','Konferenz nicht gefunden. Zugangsdaten erneut eingeben');
    }
    public function testJoinConferenceNotcorrectRoom(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1','Konferenz beitreten');
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
        $this->assertSelectorTextContains('#snackbar','Zugangsdaten in das Formular eingeben');
    }
    public function testJoinConferenceUserdoesnotexist(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1','Konferenz beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Im Browser beitreten');
        $form = $buttonCrawlerNode->form();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local6.de'));
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 1'));
        $form['join_view[uid]'] = 'wrongId123';
        $form['join_view[email]'] ='test@local6.de';
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertSelectorTextContains('#snackbar','Zugangsdaten in das Formular eingeben');
    }
    public function testJoinConferencefixedroomCorrectuserUserloginuserUserismoderatorCorrectroomnumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1','Konferenz beitreten');
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
    public function testJoinConferencefixedroomCorrectuserUserloginuserCorrectroomnumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1','Konferenz beitreten');
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
    public function testJoinConferencefixedroomCorrectuserUsernologinuserCorrectroomnumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1','Konferenz beitreten');
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
        $this->assertTrue($client->getResponse()->isRedirect($roomService->join($room,$user,'b','Test User 123')));
        $buttonCrawlerNode = $crawler->selectButton('Mit der Elektron-App beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_view[uid]'] = $room->getUid();
        $form['join_view[email]'] = $user->getEmail();
        $form['join_view[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect($roomService->join($room,$user,'a','Test User 123')));
    }
    public function testJoinConferencefixedroomNocorrectuserUsernologinuserCorrectroomnumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/join');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1','Konferenz beitreten');
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
        $this->assertSelectorTextContains('#snackbar','Konferenz nicht gefunden. Zugangsdaten erneut eingeben');
    }
}
