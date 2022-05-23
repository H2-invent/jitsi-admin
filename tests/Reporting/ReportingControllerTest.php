<?php

namespace App\Tests\Reporting;

use App\Entity\RoomStatus;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReportingControllerTest extends WebTestCase
{
    public function testOpenModal(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        // simulate $testUser being logged in
        $client->loginUser($testUser);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'Running Room'));

        $crawler = $client->request('GET', '/room/report/'.$room->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.modal-header', 'report.title');
    }

    public function testModalContent(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        // simulate $testUser being logged in
        $client->loginUser($testUser);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'Running Room'));
        $status = $room->getRoomstatuses()->toArray()[0];
        $crawler = $client->request('GET', '/room/report/'.$room->getId());

          $this->assertEquals(
              2,
              $crawler->filter('.reportTimeLine_room')->count()
          );

        $this->assertEquals(
            1,
            $crawler->filter('.timelineENdRoom')->count()
        );

        $this->assertEquals(
            4,
            $crawler->filter('.reportTimeLine_time')->count()
        );

        $this->assertEquals(
            1,
            $crawler->filter('.onlineDot')->count()
        );

        $this->assertEquals(
            1,
            $crawler->filter('.partname:contains("in der Konferenz")')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('.partname:contains("aus der Konferenz 1 Stunde")')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('.partname:contains("aus der Konferenz 1 Tag")')->count()
        );
//        echo $client->getResponse()->getContent();
        $this->assertEquals(
            1,
            $crawler->filter('.statusOpeningDate:contains("'.$status->getRoomCreatedAt()->format('H:i:s').'")')->count()
        );

        $this->assertEquals(
            1,
            $crawler->filter('.timelineENdRoom:contains("'.$status->getRoomCreatedAt()->modify('-1hour')->format('H:i:s').'")')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('.statusOpeningDate:contains("'.$status->getRoomCreatedAt()->modify('-1hours')->format('H:i:s').'")')->count()
        );

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.modal-header', 'report.title');
    }

    public function testModalContentNoReport(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        // simulate $testUser being logged in
        $client->loginUser($testUser);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'Room Yesterday'));
        $status = $room->getRoomstatuses()->toArray();
        $crawler = $client->request('GET', '/room/report/'.$room->getId());
        $this->assertEquals(
            0,
            $crawler->filter('.reportTimeLine_time')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('h4:contains("report.error.noStatus")')->count()
        );

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.modal-header', 'report.title');
    }
}
