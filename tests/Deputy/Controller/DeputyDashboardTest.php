<?php

namespace App\Tests\Deputy\Controller;

use App\Entity\Deputy;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\Deputy\DeputyService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DeputyDashboardTest extends WebTestCase
{
    public function testRoomWithDateFromManager(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $master = $userRepo->findOneBy(['email' => 'test@local.de']);
        $deputy = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $deputy2 = $userRepo->findOneBy(['email' => 'test@local3.de']);
        $client->loginUser($master);

        $client->loginUser($master);
        $server = $master->getServers()->toArray()[0];

        $crawler = $client->request('GET', '/room/new');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['room[server]'] = $server->getId();
        $form['room[name]'] = 'test von deputy';
        $form['room[start]'] = (new \DateTime())->format('Y-m-d') . 'T' . (new \DateTime())->format('H:i');
        $form['room[duration]'] = "60";
        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar .bg-success')->text();
        self::assertEquals($flashMessage, 'Die Konferenz wurde erfolgreich erstellt.');
        $rooomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $rooomRepo->findOneBy(['name' => 'test von deputy']);
        $conf = $crawler->filter('#room_card' . $room->getUidReal())->count();
        self::assertEquals($conf, 1);

        $deputyEle = new Deputy();
        $deputyEle->setDeputy($deputy)
            ->setManager($master)
            ->setCreatedAt(new \DateTime())
            ->setIsFromLdap(false);
        $master->addManagerElement($deputyEle);
        $deputy->addDeputiesElement($deputyEle);
        $client->request('GET', '/room/deputy/toggle/' . $deputy->getUid());
        $client->request('GET', '/room/dashboard');


        $client->loginUser($deputy);
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $conf = $crawler->filter('#room_card' . $room->getUidReal())->count();
        self::assertEquals($conf, 1);
        self::assertStringContainsString('Private Konferenz', $crawler->filter('#room_card' . $room->getUidReal())->text());
        $conf = $crawler->filter('#room_card' . $room->getUidReal() . ' .startDropdown')->count();
        self::assertEquals($conf, 0);
        $conf = $crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-edit')->count();
        self::assertEquals($conf, 0);
        $conf = $crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-participants')->count();
        self::assertEquals($conf, 0);


        $client->loginUser($deputy2);
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $conf = $crawler->filter('#room_card' . $room->getUidReal())->count();
        self::assertEquals($conf, 0);
    }

    public function testRoomWithDateFromDeputy(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $master = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $deputy = $userRepo->findOneBy(['email' => 'test@local.de']);
        $deputy2 = $userRepo->findOneBy(['email' => 'test@local3.de']);
        $client->loginUser($master);
        $deputyService = self::getContainer()->get(DeputyService::class);
        $deputyService->toggleDeputy($master, $deputy);
        $deputyService->toggleDeputy($master, $deputy2);

        $client->loginUser($deputy);
        $server = $deputy->getServers()[0];

        $crawler = $client->request('GET', '/room/new');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['room[server]'] = $server->getId();
        $form['room[moderator]'] = $master->getId();
        $form['room[name]'] = 'test von deputy';
        $form['room[start]'] = (new \DateTime())->format('Y-m-d') . 'T' . (new \DateTime())->format('H:i');
        $form['room[duration]'] = "60";
        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar .bg-success')->text();
        self::assertEquals($flashMessage, 'Die Konferenz wurde erfolgreich erstellt.');
        $rooomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $rooomRepo->findOneBy(['name' => 'test von deputy']);
        $conf = $crawler->filter('#room_card' . $room->getUidReal());
        self::assertEquals($conf->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .startIframe')->count(), 0);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-edit')->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-participants')->count(), 1);


        $client->loginUser($master);
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $conf = $crawler->filter('#room_card' . $room->getUidReal());
        self::assertEquals($conf->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .startIframe')->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-edit')->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-participants')->count(), 1);


        $client->loginUser($deputy2);
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $conf = $crawler->filter('#room_card' . $room->getUidReal());
        self::assertEquals($conf->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .startIframe')->count(), 0);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-edit')->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-participants')->count(), 1);
    }

    public function testRoomPersistantFromDeputy(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $master = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $deputy = $userRepo->findOneBy(['email' => 'test@local.de']);
        $deputy2 = $userRepo->findOneBy(['email' => 'test@local3.de']);
        $client->loginUser($master);
        $deputyService = self::getContainer()->get(DeputyService::class);
        $deputyService->toggleDeputy($master, $deputy);
        $deputyService->toggleDeputy($master, $deputy2);

        $client->loginUser($deputy);
        $server = $deputy->getServers()[0];

        $crawler = $client->request('GET', '/room/new');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['room[server]'] = $server->getId();
        $form['room[moderator]'] = $master->getId();
        $form['room[name]'] = 'test von deputy';
        $form['room[persistantRoom]'] = true;
        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar .bg-success')->text();
        self::assertEquals($flashMessage, 'Die Konferenz wurde erfolgreich erstellt.');
        $rooomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $rooomRepo->findOneBy(['name' => 'test von deputy']);
        $conf = $crawler->filter('#room_card' . $room->getUidReal());
        self::assertEquals($conf->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .startIframe')->count(), 0);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-edit')->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-participants')->count(), 1);


        $client->loginUser($master);
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $conf = $crawler->filter('#room_card' . $room->getUidReal());
        self::assertEquals($conf->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .startIframe')->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-edit')->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-participants')->count(), 1);


        $client->loginUser($deputy2);
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $conf = $crawler->filter('#room_card' . $room->getUidReal());
        self::assertEquals($conf->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .startIframe')->count(), 0);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-edit')->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-participants')->count(), 1);
    }

    public function testRoomPersistantFromMaster(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $master = $userRepo->findOneBy(['email' => 'test@local.de']);
        $deputy = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $deputy2 = $userRepo->findOneBy(['email' => 'test@local3.de']);
        $client->loginUser($master);
        $deputyService = self::getContainer()->get(DeputyService::class);
        $deputyService->toggleDeputy($master, $deputy);
        $deputyService->toggleDeputy($master, $deputy2);

        $client->loginUser($master);
        $server = $master->getServers()[0];

        $crawler = $client->request('GET', '/room/new');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['room[server]'] = $server->getId();
        $form['room[name]'] = 'test von deputy';
        $form['room[persistantRoom]'] = true;
        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar .bg-success')->text();
        self::assertEquals($flashMessage, 'Die Konferenz wurde erfolgreich erstellt.');
        $rooomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $rooomRepo->findOneBy(['name' => 'test von deputy']);
        $conf = $crawler->filter('#room_card' . $room->getUidReal());
        self::assertEquals($conf->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .startIframe')->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-edit')->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-participants')->count(), 1);


        $client->loginUser($deputy);
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $conf = $crawler->filter('#room_card' . $room->getUidReal());
        self::assertEquals($conf->count(), 0);


        $client->loginUser($deputy2);
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $conf = $crawler->filter('#room_card' . $room->getUidReal());
        self::assertEquals($conf->count(), 0);
    }

    public function testScheduleFromManager(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $master = $userRepo->findOneBy(['email' => 'test@local.de']);
        $deputy = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $deputy2 = $userRepo->findOneBy(['email' => 'test@local3.de']);
        $client->loginUser($master);
        $deputyService = self::getContainer()->get(DeputyService::class);
        $deputyService->toggleDeputy($master, $deputy);
        $deputyService->toggleDeputy($master, $deputy2);

        $client->loginUser($master);
        $server = $master->getServers()[0];

        $crawler = $client->request('GET', '/room/schedule/new');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['scheduler[server]'] = $server->getId();
        $form['scheduler[name]'] = 'test von deputy';
        $form['scheduler[duration]'] = 60;
        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar .bg-success')->text();
        self::assertEquals($flashMessage, 'Terminplanung erfolgreich erstellt');
        $rooomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $rooomRepo->findOneBy(['name' => 'test von deputy']);
        $conf = $crawler->filter('#room_card' . $room->getUidReal());
        self::assertEquals($conf->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .schedule-edit')->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-participants')->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .schedule-options')->count(), 1);


        $client->loginUser($deputy);
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $conf = $crawler->filter('#room_card' . $room->getUidReal());
        self::assertEquals($conf->count(), 0);


        $client->loginUser($deputy2);
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $conf = $crawler->filter('#room_card' . $room->getUidReal());
        self::assertEquals($conf->count(), 0);
    }

    public function testScheduleFromDeputy(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $master = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $deputy = $userRepo->findOneBy(['email' => 'test@local.de']);
        $deputy2 = $userRepo->findOneBy(['email' => 'test@local3.de']);
        $client->loginUser($master);
        $deputyService = self::getContainer()->get(DeputyService::class);
        $deputyService->toggleDeputy($master, $deputy);
        $deputyService->toggleDeputy($master, $deputy2);

        $client->loginUser($deputy);
        $server = $deputy->getServers()[0];

        $crawler = $client->request('GET', '/room/schedule/new');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['scheduler[server]'] = $server->getId();
        $form['scheduler[name]'] = 'test von deputy';
        $form['scheduler[moderator]'] = $master->getId();
        $form['scheduler[duration]'] = 60;
        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar .bg-success')->text();
        self::assertEquals($flashMessage, 'Terminplanung erfolgreich erstellt');
        $rooomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $rooomRepo->findOneBy(['name' => 'test von deputy']);
        $conf = $crawler->filter('#room_card' . $room->getUidReal());
        self::assertEquals($conf->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .schedule-edit')->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-participants')->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .schedule-options')->count(), 1);


        $client->loginUser($deputy);
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $conf = $crawler->filter('#room_card' . $room->getUidReal());
        self::assertEquals($conf->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .schedule-edit')->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-participants')->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .schedule-options')->count(), 1);


        $client->loginUser($deputy2);
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $conf = $crawler->filter('#room_card' . $room->getUidReal());
        self::assertEquals($conf->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .schedule-edit')->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-participants')->count(), 1);
        self::assertEquals($crawler->filter('#room_card' . $room->getUidReal() . ' .schedule-options')->count(), 1);
    }
}
