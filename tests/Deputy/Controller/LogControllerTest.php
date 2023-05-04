<?php

namespace App\Tests\Deputy\Controller;

use App\Repository\LogRepository;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\Deputy\DeputyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LogControllerTest extends WebTestCase
{
    public function testEmptyLog(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $master = $userRepo->findOneBy(['email' => 'test@local.de']);
        $deputy = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($master);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $room->setCreator($deputy);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->persist($room);
        $manager->flush();

        $crawler = $client->request('GET', '/room/change/log?room_id=' . $room->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.modal-header', 'Änderungshistorie');
        self::assertEquals(0, $crawler->filter('.card')->count());
    }

    public function testafterEdit(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $master = $userRepo->findOneBy(['email' => 'test@local.de']);
        $deputy = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($deputy);
        $deputyService = self::getContainer()->get(DeputyService::class);
        $deputyService->setDeputy($master, $deputy);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $room->setCreator($deputy);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->persist($room);
        $manager->flush();


        $crawler = $client->request('GET', '/room/new?id=' . $room->getId());
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['room[name]'] = 'Änderung durch Deputy';
        $client->submit($form);

        $client->loginUser($master);

        $crawler = $client->request('GET', '/room/change/log?room_id=' . $room->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.modal-header', 'Änderungshistorie');
        self::assertEquals(1, $crawler->filter('.card')->count());
        $logRepo = self::getContainer()->get(LogRepository::class);
        $log = $logRepo->findAll();
        self::assertEquals(1, sizeof($log));
        self::assertEquals($deputy->getId(), $log[0]->getUser()->getId());
        self::assertEquals($room->getId(), $log[0]->getRoom()->getId());
    }

    public function testNotAllowed(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $master = $userRepo->findOneBy(['email' => 'test@local.de']);
        $deputy = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($deputy);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $room->setCreator($deputy);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->persist($room);
        $manager->flush();

        $crawler = $client->request('GET', '/room/change/log?room_id=' . $room->getId());

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }
}
