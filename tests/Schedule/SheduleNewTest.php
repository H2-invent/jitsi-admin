<?php

namespace App\Tests\Schedule;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SheduleNewTest extends WebTestCase
{
    public function testCreate(): void
    {
        $client = static::createClient();

        $session = new Session(new MockFileSessionStorage());
        $session->start();

        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $client->loginUser($testUser);
        $server = $testUser->getServers()->toArray()[0];

        $crawler = $client->request('GET', '/room/schedule/new');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['room[server]'] = $server->getId();
        $form['room[name]'] = '';
        $form['room[duration]'] = "60";

        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString(json_encode(['error' => true, 'messages' => ['Fehler, bitte den Namen angeben.']]), $client->getResponse()->getContent());
        $form['room[server]'] = $server->getId();
        $form['room[name]'] = '198273987321';
        $form['room[duration]'] = "60";
        $client->submit($form);
        $room = (static::getContainer()->get(RoomsRepository::class))->findOneBy(['name' => 198273987321]);
        $urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $modalUrl = base64_encode($urlGenerator->generate('schedule_admin', ['id' => $room->getId()]));

        $this->assertJsonStringEqualsJsonString(
            json_encode(
                [
                    'error' => false,
                    'redirectUrl' => $urlGenerator->generate('dashboard'),
                    'cookie' => [
                        'room_server' => $server->getId()
                    ]
                ]
            ),
            $client->getResponse()->getContent()
        );


        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar .bg-success')->text();
        self::assertEquals($flashMessage, 'Terminplanung erfolgreich erstellt');
        self::assertStringContainsString(' var modalUrl = \'' . $modalUrl, $client->getResponse()->getContent() . '\'');
    }

    public function testRemove(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $client->loginUser($testUser);
        $server = $testUser->getServers()->toArray()[0];

        $crawler = $client->request('GET', '/room/schedule/new');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['room[server]'] = $server->getId();
        $form['room[name]'] = '198273987321';
        $form['room[duration]'] = "60";
        $client->submit($form);
        $roomRepo = static::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => '198273987321']);
        self::assertNotNull($room);
        $urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $modalUrl = base64_encode($urlGenerator->generate('schedule_admin', ['id' => $room->getId()]));
        $this->assertJsonStringEqualsJsonString(
            json_encode(
                [
                    'error' => false,
                    'redirectUrl' => $urlGenerator->generate('dashboard'),
                    'cookie' => [
                        'room_server' => $server->getId()
                    ]
                ]
            ),
            $client->getResponse()->getContent()
        );


        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar .bg-success')->text();
        self::assertEquals($flashMessage, 'Terminplanung erfolgreich erstellt');
        self::assertStringContainsString(' var modalUrl = \'' . $modalUrl, $client->getResponse()->getContent() . '\'');

        $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();

        $client->request('GET', $urlGenerator->generate('room_remove', ['room' => $room->getId()]));
        $this->assertTrue($client->getResponse()->isRedirect('/room/dashboard'));

        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar .bg-success')->text();
        self::assertEquals($flashMessage, 'Konferenz gelöscht');


        $room = $roomRepo->findOneBy(['name' => '198273987321']);
        $this->assertEquals(0, sizeof($room->getUser()));
        $this->assertNull($room->getModerator());
    }

    public function testEdit(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $client->loginUser($testUser);
        $server = $testUser->getServers()->toArray()[0];

        $crawler = $client->request('GET', '/room/schedule/new');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['room[server]'] = $server->getId();
        $form['room[name]'] = '198273987321';
        $form['room[duration]'] = "60";
        $client->submit($form);
        $roomRepo = static::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => '198273987321']);
        self::assertNotNull($room);
        $urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $modalUrl = base64_encode($urlGenerator->generate('schedule_admin', ['id' => $room->getId()]));
        $this->assertJsonStringEqualsJsonString(
            json_encode(
                [
                    'error' => false,
                    'redirectUrl' => $urlGenerator->generate('dashboard'),
                    'cookie' => [
                        'room_server' => $server->getId()
                    ]
                ]
            ),
            $client->getResponse()->getContent()
        );


        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar .bg-success')->text();
        self::assertEquals($flashMessage, 'Terminplanung erfolgreich erstellt');
        self::assertStringContainsString(' var modalUrl = \'' . $modalUrl, $client->getResponse()->getContent() . '\'');

        $client->request('GET', '/room/dashboard');
        $crawler = $client->request('GET', $urlGenerator->generate('schedule_admin_new', ['id' => $room->getId()]));
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['room[server]'] = $server->getId();
        $form['room[name]'] = '';
        $form['room[duration]'] = "60";
        $client->submit($form);
        $this->assertJsonStringEqualsJsonString(json_encode(['error' => true, 'messages' => ['Fehler, bitte den Namen angeben.']]), $client->getResponse()->getContent());

        $form['room[server]'] = $server->getId();
        $form['room[name]'] = '765456654456';
        $form['room[duration]'] = "60";
        $client->submit($form);
        $room = $roomRepo->findOneBy(['name' => '198273987321']);
        $this->assertNull($room);
        $room = $roomRepo->findOneBy(['name' => '765456654456']);
        $this->assertNotNull($room);
        $modalUrl = base64_encode($urlGenerator->generate('schedule_admin', ['id' => $room->getId()]));
        $this->assertJsonStringEqualsJsonString(
            json_encode(
                [
                    'error' => false,
                    'redirectUrl' => $urlGenerator->generate('dashboard'),
                    'cookie' => [
                        'room_server' => $server->getId()
                    ]
                ]
            ),
            $client->getResponse()->getContent()
        );

        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar .bg-success')->text();
        self::assertEquals($flashMessage, 'Terminplanung erfolgreich bearbeitet');
        self::assertStringContainsString(' var modalUrl = \'' . $modalUrl, $client->getResponse()->getContent() . '\'');
    }
}
