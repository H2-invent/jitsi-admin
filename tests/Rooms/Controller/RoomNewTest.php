<?php

namespace App\Tests\Rooms\Controller;

use App\Entity\Server;
use App\Entity\Tag;
use App\Repository\RoomsRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RoomNewTest extends WebTestCase
{

    public function testCreate(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $client->loginUser($testUser);
        $server = $testUser->getServers()->toArray()[0];

        $crawler = $client->request('GET', '/room/new');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['room[server]'] = $server->getId();
        $form['room[name]'] = '';
        $form['room[start]'] = '';
        $form['room[duration]'] = "60";

        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString(json_encode(['error' => true, 'messages' => ['Fehler, bitte das Startdatum eingeben.', 'Fehler, bitte den Namen angeben.']]), $client->getResponse()->getContent());
        $form['room[server]'] = $server->getId();
        $form['room[name]'] = '198273987321';
        $form['room[start]'] = '';
        $form['room[duration]'] = "60";
        $client->submit($form);
        $this->assertJsonStringEqualsJsonString(json_encode(['error' => true, 'messages' => ['Fehler, bitte das Startdatum eingeben.']]), $client->getResponse()->getContent());
        $form['room[server]'] = $server->getId();
        $form['room[name]'] = '198273987321';
        $form['room[start]'] = '2020-01-01T20:00:00';
        $form['room[duration]'] = "60";
        $client->submit($form);
        $this->assertJsonStringEqualsJsonString(json_encode(['error' => true, 'messages' => ['Fehler, das Startdatum und das Enddatum liegen in der Vergangenheit.']]), $client->getResponse()->getContent());
        $form['room[server]'] = $server->getId();
        $form['room[name]'] = '198273987321';
        $form['room[start]'] = (new \DateTime())->format('Y-m-d H:i:s');
        $form['room[duration]'] = "60";
        $client->submit($form);
        $room = (static::getContainer()->get(RoomsRepository::class))->findOneBy(['name' => 198273987321]);
        $urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $modalUrl = base64_encode($urlGenerator->generate('room_add_user', ['room' => $room->getId()]));
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

        $tagRepo = self::getContainer()->get(TagRepository::class);
        $tag = $tagRepo->findBy(['disabled' => false], ['priority' => 'ASC'])[0];
        self::assertEquals($tag, $room->getTag());

        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar .bg-success')->text();
        self::assertEquals($flashMessage, 'Die Konferenz wurde erfolgreich erstellt.');
        self::assertStringContainsString(' var modalUrl = \'' . $modalUrl, $client->getResponse()->getContent() . '\'');
    }
    public function testNoServer(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test2@local.de');
        $client->loginUser($testUser);
        $server = $testUser->getServers();

        $crawler = $client->request('GET', '/room/new');
        $this->assertStringContainsString(
            'Sie haben keinen Server angelegt oder es wurde Ihnen noch kein Server zugewiesen. Bitte legen Sie einen Server durch klicken auf das Zahnradsymbol in der Navigation an.',
            $client->getResponse()->getContent()
        );
    }

    public function testRemove(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $client->loginUser($testUser);
        $server = $testUser->getServers()->toArray()[0];

        $crawler = $client->request('GET', '/room/new');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['room[server]'] = $server->getId();
        $form['room[name]'] = '198273987321';
        $form['room[start]'] = (new \DateTime())->format('Y-m-d H:i:s');
        $form['room[duration]'] = "60";
        $client->submit($form);
        $roomRepo = static::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => '198273987321']);
        self::assertNotNull($room);
        $urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $modalUrl = base64_encode($urlGenerator->generate('room_add_user', ['room' => $room->getId()]));
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
        self::assertEquals($flashMessage, 'Die Konferenz wurde erfolgreich erstellt.');
        self::assertStringContainsString(' var modalUrl = \'' . $modalUrl, $client->getResponse()->getContent() . '\'');


        $client->request('GET', $urlGenerator->generate('room_favorite_toogle', ['uid' => $room->getUidReal()]));
        self::assertEquals(1, $client->request('GET', $urlGenerator->generate('dashboard'))->filter('.favoriteTitle:contains("198273987321")')->count());
        $client->request('GET', $urlGenerator->generate('room_favorite_toogle', ['uid' => $room->getUidReal()]));
        self::assertEquals(0, $client->request('GET', $urlGenerator->generate('dashboard'))->filter('.favoriteTitle:contains("198273987321")')->count());
        $client->request('GET', $urlGenerator->generate('room_favorite_toogle', ['uid' => $room->getUidReal()]));
        $client->request('GET', $urlGenerator->generate('room_remove', ['room' => $room->getId()]));
        $this->assertTrue($client->getResponse()->isRedirect('/room/dashboard'));
        $room = $roomRepo->findOneBy(['name' => '198273987321']);
        $this->assertEquals(0, sizeof($room->getUser()));
        $this->assertNull($room->getModerator());

        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar .bg-success')->text();
        self::assertEquals($flashMessage, 'Konferenz gelöscht');

        self::assertEquals(0, $client->request('GET', $urlGenerator->generate('dashboard'))->filter('.favoriteTitle:contains("198273987321")')->count());
    }

    public function testEdit(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $client->loginUser($testUser);
        $server = $testUser->getServers()->toArray()[0];

        $crawler = $client->request('GET', '/room/new');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['room[server]'] = $server->getId();
        $form['room[name]'] = '198273987321';
        $form['room[start]'] = (new \DateTime())->modify('+1hour')->format('Y-m-d H:i:s');
        $form['room[duration]'] = "60";
        $client->submit($form);
        $roomRepo = static::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => '198273987321']);
        self::assertNotNull($room);
        $urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $modalUrl = base64_encode($urlGenerator->generate('room_add_user', ['room' => $room->getId()]));
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
        self::assertEquals($flashMessage, 'Die Konferenz wurde erfolgreich erstellt.');
        self::assertStringContainsString(' var modalUrl = \'' . $modalUrl, $client->getResponse()->getContent() . '\'');

        $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();

        $crawler = $client->request('GET', $urlGenerator->generate('room_new', ['id' => $room->getId()]));
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['room[server]'] = $server->getId();
        $form['room[name]'] = '';
        $form['room[start]'] = (new \DateTime())->modify('+2hours')->format('Y-m-d H:i:s');
        $form['room[duration]'] = "60";
        $client->submit($form);
        $this->assertJsonStringEqualsJsonString(json_encode(['error' => true, 'messages' => ['Fehler, bitte den Namen angeben.']]), $client->getResponse()->getContent());
        $form['room[server]'] = $server->getId();
        $form['room[name]'] = '765456654456';
        $form['room[start]'] = '';
        $form['room[duration]'] = "60";
        $client->submit($form);
        $this->assertJsonStringEqualsJsonString(json_encode(['error' => true, 'messages' => ['Fehler, bitte das Startdatum eingeben.']]), $client->getResponse()->getContent());
        $form['room[server]'] = $server->getId();
        $form['room[name]'] = '';
        $form['room[start]'] = '';
        $form['room[duration]'] = "60";
        $client->submit($form);
        $this->assertJsonStringEqualsJsonString(json_encode(['error' => true, 'messages' => ['Fehler, bitte das Startdatum eingeben.', 'Fehler, bitte den Namen angeben.']]), $client->getResponse()->getContent());
        $form['room[server]'] = $server->getId();
        $form['room[name]'] = 'test';
        $form['room[start]'] = '2020-01-01T20:00:00';
        $form['room[duration]'] = "60";
        $client->submit($form);
        $this->assertJsonStringEqualsJsonString(json_encode(['error' => true, 'messages' => ['Fehler, das Startdatum und das Enddatum liegen in der Vergangenheit.']]), $client->getResponse()->getContent());

        $form['room[server]'] = $server->getId();
        $form['room[name]'] = '765456654456';
        $form['room[start]'] = (new \DateTime())->format('Y-m-d H:i:s');
        $form['room[duration]'] = "60";

        $client->submit($form);
        $room = $roomRepo->findOneBy(['name' => '198273987321']);
        $this->assertNull($room);
        $room = $roomRepo->findOneBy(['name' => '765456654456']);
        $this->assertNotNull($room);
        $modalUrl = base64_encode($urlGenerator->generate('room_add_user', ['room' => $room->getId()]));
        $test = $client->getResponse()->getContent();
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
            $test
        );

        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar .bg-success')->text();
        self::assertEquals($flashMessage, 'Die Konferenz wurde erfolgreich bearbeitet.');
        self::assertStringContainsString(' var modalUrl = \'' . $modalUrl, $client->getResponse()->getContent() . '\'');
    }

    public function testClone(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $client->loginUser($testUser);
        $server = $testUser->getServers()->toArray()[0];

        $crawler = $client->request('GET', '/room/new');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['room[server]'] = $server->getId();
        $form['room[name]'] = '198273987321';
        $form['room[start]'] = (new \DateTime())->modify('+1hour')->format('Y-m-d H:i:s');
        $form['room[duration]'] = "60";
        $client->submit($form);
        $roomRepo = static::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => '198273987321']);
        self::assertNotNull($room);
        $urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $modalUrl = base64_encode($urlGenerator->generate('room_add_user', ['room' => $room->getId()]));
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
        self::assertEquals($flashMessage, 'Die Konferenz wurde erfolgreich erstellt.');
        self::assertStringContainsString(' var modalUrl = \'' . $modalUrl, $client->getResponse()->getContent() . '\'');

        $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();

        $crawler = $client->request('GET', $urlGenerator->generate('room_clone', ['room' => $room->getId()]));
        self::assertResponseIsSuccessful();
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['room[name]'] = 'Roome Clone';
        $form['room[start]'] = (new \DateTime())->modify('+2hours')->format('Y-m-d H:i:s');

        $client->submit($form);
        $room = $roomRepo->findOneBy(['name' => 'Roome Clone']);
        $this->assertNotNull($room);
        $modalUrl = base64_encode($urlGenerator->generate('room_add_user', ['room' => $room->getId()]));
        $test = $client->getResponse()->getContent();
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
            $test
        );

        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar .bg-success')->text();
        self::assertEquals($flashMessage, 'Die Konferenz wurde erfolgreich erstellt.');
        self::assertStringContainsString(' var modalUrl = \'' . $modalUrl, $client->getResponse()->getContent() . '\'');
    }

    public function testEditRunningRoom(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $client->loginUser($testUser);
        $server = $testUser->getServers()->toArray()[0];

        $crawler = $client->request('GET', '/room/new');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['room[server]'] = $server->getId();
        $form['room[name]'] = '198273987321';
        $form['room[agenda]'] = 'this is an agenda for this meeting';
        $form['room[start]'] = (new \DateTime())->format('Y-m-d H:i:s');
        $form['room[duration]'] = "60";
        $client->submit($form);
        $roomRepo = static::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => '198273987321']);
        self::assertEquals('this is an agenda for this meeting', $room->getAgenda());
        self::assertNotNull($room);


        $urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $modalUrl = base64_encode($urlGenerator->generate('room_add_user', ['room' => $room->getId()]));
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
        self::assertEquals($flashMessage, 'Die Konferenz wurde erfolgreich erstellt.');
        self::assertStringContainsString(' var modalUrl = \'' . $modalUrl, $client->getResponse()->getContent() . '\'');

        $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();

        $crawler = $client->request('GET', $urlGenerator->generate('room_new', ['id' => $room->getId()]));

        $disabled = [];
        foreach ($crawler->filter('[disabled=disabled]') as $content) {
            $disabled[] = $content;
        }
        self::assertEquals(2, sizeof($disabled));
        self::assertEquals('room_name', $disabled[0]->getAttribute('id'));
        self::assertEquals('room_agenda', $disabled[1]->getAttribute('id'));


        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['room[name]'] = '';
        $form['room[agenda]'] = '';
        $client->submit($form);
        $room = $roomRepo->find($room->getId());

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
        self::assertEquals('198273987321', $room->getName());
        self::assertEquals('this is an agenda for this meeting', $room->getAgenda());
    }
}
