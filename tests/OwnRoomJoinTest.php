<?php

namespace App\Tests;

use App\Repository\RoomsRepository;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use App\Service\RoomService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OwnRoomJoinTest extends WebTestCase
{
    public function testServerhasLicense(): void
    {
        $client = static::createClient();
        $room = $this->getRoomByName('Room with Start and no Participants list');
        $crawler = $client->request('GET', '/myRoom/start/' . $room->getUid());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', $room->getName());
        $this->assertStringNotContainsString('https://privacy.dev', $client->getResponse()->getContent());
        $this->assertStringContainsString('https://test.img', $client->getResponse()->getContent());
    }

    public function testWithNoLicenseServer(): void
    {
        $client = static::createClient();
        $room = $this->getRoomByName('Room with Start and no Participants list');
        $manager = $this->getContainer()->get(EntityManagerInterface::class);
        $server = $room->getServer();
        $server->setLicenseKey(null);
        $manager->persist($server);
        $manager->flush();
        $room = $this->getRoomByName('Room with Start and no Participants list');
        $crawler = $client->request('GET', '/myRoom/start/' . $room->getUid());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', $room->getName());
        $this->assertStringNotContainsString('https://privacy.dev', $client->getResponse()->getContent());
        $this->assertStringNotContainsString('https://test.img', $client->getResponse()->getContent());
    }

    public function testOpenRoomNomoderator(): void
    {
        $client = static::createClient();
        $room = $this->getRoomByName('Room with Start and no Participants list');
        $crawler = $client->request('GET', '/myRoom/start/' . $room->getUid());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', $room->getName());
        $buttonCrawlerNode = $crawler->selectButton('Im Browser beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_my_room[name]'] = 'Test User 123';
        $client->submit($form);
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
        $this->assertTrue($client->getResponse()->isRedirect($urlGenerator->generate('room_waiting', array('name' => 'Test User 123', 'uid' => $room->getUid(), 'type' => 'b'))));
        $buttonCrawlerNode = $crawler->selectButton('Mit der Elektron-App beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_my_room[name]'] = 'Test User 123';
        $client->submit($form);
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
        $this->assertTrue($client->getResponse()->isRedirect($urlGenerator->generate('room_waiting', array('name' => 'Test User 123', 'uid' => $room->getUid(), 'type' => 'a'))));
    }


    public function testOpenRoomIsModerator(): void
    {
        $client = static::createClient();
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $client->loginUser($user);
        $room = $this->getRoomByName('Room with Start and no Participants list');
        $crawler = $client->request('GET', '/myRoom/start/' . $room->getUid());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', $room->getName());
        $buttonCrawlerNode = $crawler->selectButton('Im Browser beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_my_room[name]'] = 'Test User 123';
        $client->submit($form);
        $urlGenerator = self::getContainer()->get(RoomService::class);
        $this->assertTrue($client->getResponse()->isRedirect($urlGenerator->createUrl('b', $room, true, null, 'Test User 123')));
        $buttonCrawlerNode = $crawler->selectButton('Mit der Elektron-App beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_my_room[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect($urlGenerator->createUrl('a', $room, true, null, 'Test User 123')));
    }

    public function testmyWaiting(): void
    {
        $client = static::createClient();
        $urlGenerator = self::getContainer()->get(RoomService::class);
        $url = self::getContainer()->get(UrlGeneratorInterface::class);
        $room = $this->getRoomByName('Room with Start and no Participants list');
        $manager = $this->getContainer()->get(EntityManagerInterface::class);
        $room->setStart((new \DateTime())->modify('+10min'));
        $manager->persist($room);
        $manager->flush();
        $crawler = $client->request('GET', '/mywaiting/check/' . $room->getUid() . '/Test User 123/a');
        $this->assertResponseIsSuccessful();
        $this->assertEquals('{"error":true}',$client->getResponse()->getContent());
        $room->setStart((new \DateTime())->modify('-10min'));
        $manager->persist($room);
        $manager->flush();
        $crawler = $client->request('GET', $url->generate('room_waiting',array('uid'=>$room->getUid(),'name'=>'Test User 123','type'=>'a')));
        $this->assertSelectorTextContains('h2','Bitte warten. Die Konferenz ist noch nicht geöffnet');
        $crawler = $client->request('GET', '/mywaiting/check/' . $room->getUid() . '/Test User 123/a');
        $this->assertResponseIsSuccessful();
        self::assertEquals(json_encode(
            array('error'=>false,
                'url'=>$urlGenerator->createUrl('a',$room,false,null,'Test User 123'))
        ),$client->getResponse()->getContent());
    }

    public function testtotalOpenRoomsNoParticipantList(): void
    {
        $client = static::createClient();
        $user = $this->getUSerByEmail('test@local2.de');
        $client->loginUser($user);
        $urlGenerator = self::getContainer()->get(RoomService::class);
        $url = self::getContainer()->get(UrlGeneratorInterface::class);
        $room = $this->getRoomByName('This Room has no participants and fixed room');
        $crawler = $client->request('GET', '/mywaiting/check/' . $room->getUid() . '/Test User 123/a');
        $this->assertResponseIsSuccessful();
        $this->assertEquals('{"error":true}',$client->getResponse()->getContent());
        $crawler = $client->request('GET', $url->generate('room_waiting',array('uid'=>$room->getUid(),'name'=>'Test User 123','type'=>'a')));
        $this->assertSelectorTextContains('h2','Bitte warten. Die Konferenz ist noch nicht geöffnet');
        $user = $this->getUSerByEmail('test@local.de');
        $client->loginUser($user);
        $crawler = $client->request('GET', '/myRoom/start/' . $room->getUid());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', $room->getName());
        $buttonCrawlerNode = $crawler->selectButton('Im Browser beitreten');
        $buttonCrawlerNode = $crawler->selectButton('Im Browser beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_my_room[name]'] = 'Test User 123';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect($urlGenerator->joinUrl('b',$room,'Test User 123',true)));
        $parameterBag = $this->getContainer()->get(ParameterBagInterface::class);
        $crawler = $client->request('GET', $url->generate('room_join',array('t'=>'b','room'=>$room->getId())));
        $this->assertTrue($client->getResponse()->isRedirect($urlGenerator->joinUrl('b',$room,$user->getFormatedName($parameterBag->get('laf_showNameInConference')),true)));

        $user = $this->getUSerByEmail('test@local2.de');
        $client->loginUser($user);
        $crawler = $client->request('GET', '/mywaiting/check/' . $room->getUid() . '/Test User 123/a');
        $this->assertResponseIsSuccessful();
        self::assertEquals(json_encode(
            array('error'=>false,
                'url'=>$urlGenerator->createUrl('a',$room,false,null,'Test User 123'))
        ),$client->getResponse()->getContent());
    }
    public function getRoomByName($name)
    {
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => $name));
        return $room;
    }
    public function getUSerByEmail($name)
    {
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => $name));
        return $user;
    }
}
