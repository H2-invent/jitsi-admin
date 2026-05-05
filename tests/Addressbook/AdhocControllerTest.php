<?php

namespace App\Tests\Addressbook;

use App\Repository\RoomsRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Service\Lobby\DirectSendService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;

class AdhocControllerTest extends WebTestCase
{
    public function testcreateAdhocMeetingNoTag(): void
    {
        $client = static::createClient();


        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                $data = $update->getData();
                $tmp = json_decode($data, true);
                if ($tmp['type'] === "call") {
                    self::assertStringContainsString('{"type":"call","title":"Ad Hoc Meeting"', $update->getData());
                    self::assertEquals('Ad Hoc Meeting', $tmp['title']);
                    self::assertEquals(['personal/kljlsdkjflkjddfgslfjsdlkjsdflkj'], $update->getTopics());
                } elseif (str_contains($data, '"type":"notification"')) {
                    self::assertEquals('[Videokonferenz] Es gibt eine neue Einladung zur Videokonferenz Konferenz mit Test1, 1234, User, Test.', $tmp['title']);
                    self::assertEquals(['personal/kljlsdkjflkjddfgslfjsdlkjsdflkj'], $update->getTopics());
                }
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);


        $crawler = $client->request('GET', '/room/adhoc/meeting/' . $user2->getId() . '/' . $user->getServers()[0]->getId());
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'Konferenz mit Test1, 1234, User, Test'));
        self::assertEquals(
            json_encode(
                ['redirectUrl' => '/room/dashboard',
                    'popups' => [
                        [
                            'url' => 'http://localhost/room/join/b/' . $room->getId(),
                            'title' => 'Konferenz mit Test2, 1234, User2, Test2']
                    ]
                ]
            ),
            $client->getResponse()->getContent()
        );
        $crawler = $client->request('GET', json_decode($client->getResponse()->getContent(), true)['popups'][0]['url']);
        self::assertSelectorNotExists('#tagContent');

        $crawler = $client->request('GET', '/room/dashboard');

        self::assertEquals(1, $crawler->filter('h5:contains("Konferenz mit Test2, 1234, User2, Test2")')->count());
        self::assertEquals(0, $crawler->filter('h5:contains("Konferenz mit Test1, 1234, User, Test")')->count());
        $client->loginUser($user2);
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertEquals(1, $crawler->filter('h5:contains("Konferenz mit Test1, 1234, User, Test")')->count());
        self::assertEquals(0, $crawler->filter('h5:contains("Konferenz mit Test2, 1234, User2, Test2")')->count());
    }

    public function testcreateAdhocMeetingWithTag(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($user);

        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                $data = $update->getData();
                $tmp = json_decode($data, true);
                if ($tmp['type'] === "call") {
                    self::assertStringContainsString('{"type":"call","title":"Ad Hoc Meeting"', $update->getData());
                    self::assertEquals('Ad Hoc Meeting', $tmp['title']);
                    self::assertEquals(['personal/kljlsdkjflkjddfgslfjsdlkjsdflkj'], $update->getTopics());
                } elseif (str_contains($data, '"type":"notification"')) {
                    self::assertEquals('[Videokonferenz] Es gibt eine neue Einladung zur Videokonferenz Konferenz mit Test1, 1234, User, Test.', $tmp['title']);
                    self::assertEquals(['personal/kljlsdkjflkjddfgslfjsdlkjsdflkj'], $update->getTopics());
                }
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);


        $tagRepo = self::getContainer()->get(TagRepository::class);
        $tag = $tagRepo->findOneBy(['title' => 'Test Tag Enabled']);
        $crawler = $client->request('GET', '/room/adhoc/meeting/' . $user2->getId() . '/' . $user->getServers()[0]->getId() . '/' . $tag->getId());
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'Konferenz mit Test1, 1234, User, Test'));

        self::assertEquals(
            json_encode(
                [
                    'redirectUrl' => '/room/dashboard',
                    'popups' => [
                        [
                            'url' => 'http://localhost/room/join/b/' . $room->getId(),
                            'title' => 'Konferenz mit Test2, 1234, User2, Test2']
                    ]
                ]
            ),
            $client->getResponse()->getContent()
        );
        $crawler = $client->request('GET', json_decode($client->getResponse()->getContent(), true)['popups'][0]['url']);
        self::assertSelectorTextContains('#tagContent', 'Test Tag Enabled');
        self::assertResponseIsSuccessful();
    }
}
