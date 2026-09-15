<?php

namespace App\Tests\Repeater;

use App\Entity\Repeat;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RepeaterControllerTest extends WebTestCase
{
    public function testRepeaterControllerCreateEditDelete(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $crawler = $client->request('GET', '/room/repeater/new?room=' . $room->getId());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h5', 'Serientermin festlegen');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['repeater[repeatType]'] = 0;
        $form['repeater[repeaterDays]'] = 1;
        $form['repeater[repetation]'] = 10;
        $client->submit($form);


        self::assertTrue($client->getResponse()->isRedirect('/room/dashboard'));

        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar .bg-success')->text();
        self::assertEquals($flashMessage, 'Sie haben erfolgreich einen Serientermin erstellt.');


        $rooms = $roomRepo->findBy(['name' => 'TestMeeting: 0'],['start'=>'ASC']);
        self::assertEquals(11, sizeof($rooms));
        $start = $room->getStart();
        $start = $start->setTime($start->format('H'), $start->format('i'), 0);

        foreach ($rooms as $data) {


            if ($data->getRepeater()) {

                self::assertEquals($start, $data->getStart());
                $start = $start->modify('+1day');
            } else {
                self::assertEquals($data->getStart(), $data->getRepeaterProtoype()->getStartDate());
            }
        }
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertEquals(10, $crawler->filter('.h5-responsive:contains("TestMeeting: 0")')->count());

        //Edit the prototype to change all Rooms
        $crawler = $client->request('GET', '/room/repeater/edit/room?id=' . $rooms[5]->getId() . '&type=all');


        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['room[start]'] = '2022-04-10T12:00:00';
        $client->submit($form);

        self::assertEquals('{"error":false,"redirectUrl":"\/room\/dashboard?snack=Sie%20haben%20erfolgreich%20einen%20Serientermin%20bearbeitet.\u0026color=success"}', $client->getResponse()->getContent());

        $rooms = $roomRepo->findBy(['name' => 'TestMeeting: 0'],['start'=>'ASC']);
        self::assertEquals(11, sizeof($rooms));
        $start = new \DateTimeImmutable('2022-04-10T12:00:00');
        $start = $start->setTime($start->format('H'), $start->format('i'), 0);
        foreach ($rooms as $data) {
            if ($data->getRepeater()) {
                self::assertEquals($start, $data->getStart());
                $start = $start->modify('+1day');
            } else {
                self::assertEquals($data->getStart(), $data->getRepeaterProtoype()->getStartDate());
            }
        }
        //edit the repeateer Type
        $crawler = $client->request('GET', '/room/repeater/edit/repeat?repeat=' . $rooms[5]->getRepeater()->getId());


        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['repeater[repetation]'] = 3;
        $form['repeater[repeaterDays]'] = 3;
        $client->submit($form);


        self::assertTrue($client->getResponse()->isRedirect('/room/dashboard'));

        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar .bg-success')->text();
        self::assertEquals($flashMessage, 'Sie haben erfolgreich einen Serientermin bearbeitet.');

        $rooms = $roomRepo->findBy(['name' => 'TestMeeting: 0'],['start'=>'ASC']);
        self::assertEquals(4, sizeof($rooms));
        $start = new \DateTimeImmutable('2022-04-10T12:00:00');
        foreach ($rooms as $data) {
            if ($data->getRepeater()) {
                self::assertEquals($start, $data->getStart());
                $start = $start->modify('+3days');
            } else {
                self::assertEquals($data->getStart(), $data->getRepeaterProtoype()->getStartDate());
            }
        }

        $crawler = $client->request('GET', '/room/repeater/remove?repeat=' . $rooms[2]->getRepeater()->getId());
        $rooms = $roomRepo->findBy(['name' => 'TestMeeting: 0'],['start'=>'ASC']);
        self::assertEquals(4, sizeof($rooms));
        foreach ($rooms as $data) {
            self::assertEquals(0, sizeof($data->getUser()));
        }
    }

    public function testRepeaterCreationRejectsMoreThanMaxRepetitions(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);
        $max = (int)self::getContainer()->get(ParameterBagInterface::class)->get('laf_max_repeat');
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);

        $crawler = $client->request('GET', '/room/repeater/new?room=' . $room->getId());
        $form = $crawler->selectButton('Speichern')->form();
        $form['repeater[repeatType]'] = '0';
        $form['repeater[repeaterDays]'] = '1';
        $form['repeater[repetation]'] = (string)($max + 1);
        $client->submit($form);

        self::assertTrue($client->getResponse()->isRedirect('/room/dashboard'));

        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            'Sie dürfen nur maximal ' . $max . ' Wiederholungen angeben',
            $crawler->filter('.snackbar .bg-danger')->text()
        );
        self::assertCount(1, $roomRepo->findBy(['name' => 'TestMeeting: 0']));
    }

    public function testRepeaterCreationAllowsExactlyMaxRepetitions(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);
        $max = (int)self::getContainer()->get(ParameterBagInterface::class)->get('laf_max_repeat');
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);

        $crawler = $client->request('GET', '/room/repeater/new?room=' . $room->getId());
        $form = $crawler->selectButton('Speichern')->form();
        $form['repeater[repeatType]'] = '0';
        $form['repeater[repeaterDays]'] = '1';
        $form['repeater[repetation]'] = (string)$max;
        $client->submit($form);

        self::assertTrue($client->getResponse()->isRedirect('/room/dashboard'));
        self::assertCount($max + 1, $roomRepo->findBy(['name' => 'TestMeeting: 0']));
    }

    public function testRepeaterNewIsForbiddenForNonOrganizer(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($user);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);

        $client->request('GET', '/room/repeater/new?room=' . $room->getId());
        $this->assertResponseStatusCodeSame(404);
    }

    public function testRepeaterEditIsForbiddenForNonOrganizer(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $repeat = new Repeat();
        $repeat->setRepeatType(0);
        $repeat->setRepetation(1);
        $repeat->setRepeaterDays(1);
        $repeat->setStartDate($room->getStart());
        $repeat->setPrototyp($room);
        $repeat->setUid('forbidden-edit-test');
        $manager->persist($repeat);
        $manager->flush();

        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/repeater/edit/repeat?repeat=' . $repeat->getId());
        $this->assertResponseStatusCodeSame(404);
    }
}
