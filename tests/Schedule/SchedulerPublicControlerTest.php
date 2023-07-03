<?php

namespace App\Tests\Schedule;

use App\Repository\RoomsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use function PHPUnit\Framework\assertEquals;

class SchedulerPublicControlerTest extends WebTestCase
{
    public function testAddSchedulingTimePublicModal(): void
    {
        $client = static::createClient();

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $umfrage = $roomRepo->findOneBy(['name' => 'Termin finden: 0']);
        self::assertEquals(5, count($umfrage->getSchedulings()[0]->getSchedulingTimes()));
        $user = $umfrage->getUser()[1];


        $crawler = $client->request('GET', '/scheduler/public/creator/?user_id=' . $user->getUid() . '&room_id=' . $umfrage->getUid());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.modal-title', 'Terminplaner');
        assertEquals(5, $crawler->filter('.list-group-item')->count());
    }

    public function testAddSchedulingTimePublicModalNoRoom(): void
    {
        $client = static::createClient();

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $umfrage = $roomRepo->findOneBy(['name' => 'Termin finden: 0']);
        self::assertEquals(5, count($umfrage->getSchedulings()[0]->getSchedulingTimes()));
        $user = $umfrage->getUser()[1];


        $crawler = $client->request('GET', '/scheduler/public/creator/?user_id=' . $user->getUid() . '&room_id=failure');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());

    }

    public function testAddSchedulingTimePublicModalNoUser(): void
    {
        $client = static::createClient();

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $umfrage = $roomRepo->findOneBy(['name' => 'Termin finden: 0']);
        self::assertEquals(5, count($umfrage->getSchedulings()[0]->getSchedulingTimes()));
        $user = $umfrage->getUser()[1];


        $crawler = $client->request('GET', '/scheduler/public/creator/?user_id=failure' . '&room_id=' . $umfrage->getUid());
        $this->assertEquals(404, $client->getResponse()->getStatusCode());

    }

    public function testAddSchedulingTimePublicAddNewTime(): void
    {
        $client = static::createClient();

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $umfrage = $roomRepo->findOneBy(['name' => 'Termin finden: 0']);
        self::assertEquals(5, count($umfrage->getSchedulings()[0]->getSchedulingTimes()));
        $user = $umfrage->getUser()[1];
        $date = (new \DateTime())->modify('+10days');
        $date->setTime(15, 00);
        $crawler = $client->request('GET', '/scheduler/public/creator/add?user_id=' . $user->getUid() . '&room_id=' . $umfrage->getUid() . '&date=' . $date->format('Y-m-d H:i'));
        self::assertEquals(json_encode(['error' => false]), $client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $umfrage = $roomRepo->findOneBy(['name' => 'Termin finden: 0']);
        $schedule = $umfrage->getSchedulings()[0];
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->refresh($umfrage);
        self::assertEquals(6, count($schedule->getSchedulingTimes()));
        self::assertEquals($date, $schedule->getSchedulingTimes()[5]->getTime());
        $this->assertEmailCount(2);
        $email = $this->getMailerMessage();

        $this->assertEmailHtmlBodyContains($email, 'Es wurde ein neuer Terminvorschlag hinzugefügt.');

        $crawler = $client->request('GET', '/scheduler/public/creator/?user_id=' . $user->getUid() . '&room_id=' . $umfrage->getUid());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.modal-title', 'Terminplaner');
        assertEquals(6, $crawler->filter('.list-group-item')->count());
    }

    public function testAddSchedulingTimePublicAddNewTimeFailureNoUser(): void
    {
        $client = static::createClient();

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $umfrage = $roomRepo->findOneBy(['name' => 'Termin finden: 0']);
        self::assertEquals(5, count($umfrage->getSchedulings()[0]->getSchedulingTimes()));
        $user = $umfrage->getUser()[1];
        $date = (new \DateTime())->modify('+10days');
        $date->setTime(15, 00);
        $crawler = $client->request('GET', '/scheduler/public/creator/add?user_id=failure' . '&room_id=' . $umfrage->getUid() . '&date=' . $date->format('Y-m-d H:i'));
        self::assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testAddSchedulingTimePublicAddNewTimeFailureNoRoom(): void
    {
        $client = static::createClient();

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $umfrage = $roomRepo->findOneBy(['name' => 'Termin finden: 0']);
        self::assertEquals(5, count($umfrage->getSchedulings()[0]->getSchedulingTimes()));
        $user = $umfrage->getUser()[1];
        $date = (new \DateTime())->modify('+10days');
        $date->setTime(15, 00);
        $crawler = $client->request('GET', '/scheduler/public/creator/add?user_id=' . $user->getUid() . '&room_id=failure' . '&date=' . $date->format('Y-m-d H:i'));
        self::assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testAddSchedulingTimePublicAddNewTimeWrongDateTime(): void
    {
        $client = static::createClient();

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $umfrage = $roomRepo->findOneBy(['name' => 'Termin finden: 0']);
        self::assertEquals(5, count($umfrage->getSchedulings()[0]->getSchedulingTimes()));
        $user = $umfrage->getUser()[1];
        $date = (new \DateTime())->modify('+10days');
        $date->setTime(15, 00);
        $crawler = $client->request('GET', '/scheduler/public/creator/add?user_id=' . $user->getUid() . '&room_id=' . $umfrage->getUid() . '&date=' . $date->format('Y-m-dfailureH:i'));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        self::assertEquals(json_encode(['error' => true]), $client->getResponse()->getContent());
    }

}
