<?php

namespace App\Tests\Addressbook;

use App\Repository\RoomsRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Service\adhocmeeting\AdhocMeetingService;
use App\Service\Lobby\DirectSendService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;

class AdhocControllerTest extends WebTestCase
{

    public function testcreateAdhocMeetingNoTag(): void
    {
        $client = static::createClient();


        $adhockservice = self::getContainer()->get(AdhocMeetingService::class);
        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub('http://localhost:3000/.well-known/mercure', new StaticTokenProvider('test'), function (Update $update): string {
            $data = $update->getData();
            $tmp = json_decode($data, true);
            if ($tmp['type'] === "call") {
                self::assertStringContainsString('{"type":"call","title":"Ad Hoc Meeting"', $update->getData());
                self::assertEquals('Ad Hoc Meeting', $tmp['title']);
                self::assertEquals(['personal/kljlsdkjflkjddfgslfjsdlkjsdflkj'], $update->getTopics());
            } elseif (str_contains($data, '"type":"notification"')) {
                self::assertEquals('[Videokonferenz] Es gibt eine neue Einladung zur Videokonferenz Konferenz mit Test2, 1234, User2, Test2.', $tmp['title']);
                self::assertEquals(['personal/kljlsdkjflkjddfgslfjsdlkjsdflkj'], $update->getTopics());
            }
            return 'id';
        });
        $directSend->setMercurePublisher($hub);

        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $user2 = $userRepo->findOneBy(array('email' => 'test@local2.de'));
        $client->loginUser($user);
        $crawler = $client->request('GET', '/room/adhoc/meeting/' . $user2->getId() . '/' . $user->getServers()[0]->getId());
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findAll();
        $room = $room[sizeof($room)-1];
        self::assertEquals(json_encode(
            array('redirectUrl' => '/room/dashboard','popups'=>array('/room/join/b/'.$room->getId()))), $client->getResponse()->getContent());
        $crawler = $client->request('GET', json_decode($client->getResponse()->getContent(),true)['popups'][0]);
        self::assertSelectorNotExists('#tagContent');
    }

    public function testcreateAdhocMeetingWithTag(): void
    {
        $client = static::createClient();


        $adhockservice = self::getContainer()->get(AdhocMeetingService::class);
        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub('http://localhost:3000/.well-known/mercure', new StaticTokenProvider('test'), function (Update $update): string {
            $data = $update->getData();
            $tmp = json_decode($data, true);
            if ($tmp['type'] === "call") {
                self::assertStringContainsString('{"type":"call","title":"Ad Hoc Meeting"', $update->getData());
                self::assertEquals('Ad Hoc Meeting', $tmp['title']);
                self::assertEquals(['personal/kljlsdkjflkjddfgslfjsdlkjsdflkj'], $update->getTopics());
            } elseif (str_contains($data, '"type":"notification"')) {
                self::assertEquals('[Videokonferenz] Es gibt eine neue Einladung zur Videokonferenz Konferenz mit Test2, 1234, User2, Test2.', $tmp['title']);
                self::assertEquals(['personal/kljlsdkjflkjddfgslfjsdlkjsdflkj'], $update->getTopics());
            }
            return 'id';
        });
        $directSend->setMercurePublisher($hub);

        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $user2 = $userRepo->findOneBy(array('email' => 'test@local2.de'));
        $client->loginUser($user);
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $tag = $tagRepo->findOneBy(array('title'=>'Test Tag Enabled'));
        $crawler = $client->request('GET', '/room/adhoc/meeting/' . $user2->getId() . '/' . $user->getServers()[0]->getId().'/'.$tag->getId());
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findAll();
        $room = $room[sizeof($room)-1];

        self::assertEquals(json_encode(
            array('redirectUrl' => '/room/dashboard','popups'=>array('/room/join/b/'.$room->getId()))), $client->getResponse()->getContent());
        $crawler = $client->request('GET', json_decode($client->getResponse()->getContent(),true)['popups'][0]);
        self::assertSelectorTextContains('#tagContent','Test Tag Enabled');
        self::assertResponseIsSuccessful();
    }

}
