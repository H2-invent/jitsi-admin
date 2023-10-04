<?php

namespace App\Tests\Reporting;

use App\Entity\RoomStatus;
use App\Entity\RoomStatusParticipant;
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
        $room = $roomRepo->findOneBy(['name' => 'Running Room']);

        $crawler = $client->request('GET', '/room/report/' . $room->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.modal-header', 'Protokoll');
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
        $room = $roomRepo->findOneBy(['name' => 'Running Room']);
        $status = $room->getRoomstatuses()->toArray()[0];
        $crawler = $client->request('GET', '/room/report/' . $room->getId());

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
            $crawler->filter('.online')->count()
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
        $this->assertEquals(
            2,
            $crawler->filter('.statusOpeningDate')->count()
        );



        $status = $room->getRoomstatuses()->toArray()[1];
        $roomstart = $status->getRoomCreatedAtwithTimeZone($testUser);
        $roomEnd = $status->getDestroyedAtwithTimeZone($testUser);
        $this->assertEquals(
            1,
            $crawler->filter('.statusOpeningDate:contains("' . $roomstart->format('H:i:s') . '")')->count()
        );

        $this->assertEquals(
            1,
            $crawler->filter('.timelineENdRoom:contains("' . $roomEnd->format('H:i:s') . '")')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('.speakerTime:contains("03:05")')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('.speakerTime:contains("100,0%")')->count()
        );

        $this->assertEquals(
            1,
            $crawler->filter('.speakerTime:contains("Aktive Sprechzeit")')->count()
        );

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.modal-header', 'Protokoll');
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
        $room = $roomRepo->findOneBy(['name' => 'Room Yesterday']);
        $status = $room->getRoomstatuses()->toArray();
        $crawler = $client->request('GET', '/room/report/' . $room->getId());
        $this->assertEquals(
            0,
            $crawler->filter('.reportTimeLine_time')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('h4:contains("Diese Konferenz hat bisher keine Reports zum Anzeigen.")')->count()
        );

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.modal-header', 'Protokoll');
    }

    public function testModalContentAustralia(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@australia.de');
        // simulate $testUser being logged in
        $client->loginUser($testUser);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'Running Room']);
        $room->setModerator($testUser);
        $room->addUser($testUser);
        $status = $room->getRoomstatuses()->toArray()[0];
        $crawler = $client->request('GET', '/room/report/' . $room->getId());
        $this->assertEquals(
            1,
            $crawler->filter('.modal-content:contains("Australia/Lindeman")')->count()
        );

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
            $crawler->filter('.online')->count()
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

        $status = $room->getRoomstatuses()->toArray()[0];
        $roomstart = $status->getRoomCreatedAtwithTimeZone($testUser);
        $roomEnd = $status->getDestroyedAtwithTimeZone($testUser);
        $this->assertEquals(
            1,
            $crawler->filter('.statusOpeningDate:contains("' . $status->getRoomCreatedAtwithTimeZone($testUser)->format('H:i:s') . '")')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('.statusOpeningDate:contains("' . $status->getRoomCreatedAtwithTimeZone($testUser)->format('H:i:s') . '")')->count()
        );


        $status = $room->getRoomstatuses()->toArray()[1];
        $roomstart = $status->getRoomCreatedAtwithTimeZone($testUser);
        $roomEnd = $status->getDestroyedAtwithTimeZone($testUser);
        $this->assertEquals(
            1,
            $crawler->filter('.statusOpeningDate:contains("' . $roomstart->format('H:i:s') . '")')->count()
        );

        $this->assertEquals(
            1,
            $crawler->filter('.timelineENdRoom:contains("' . $roomEnd->format('H:i:s') . '")')->count()
        );

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.modal-header', 'Protokoll');
    }

    public function testModalContentNoTimeZone(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@noTimeZone.de');
        // simulate $testUser being logged in
        $client->loginUser($testUser);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'Running Room']);
        $room->setModerator($testUser);
        $room->addUser($testUser);
        $status = $room->getRoomstatuses()->toArray()[0];
        $crawler = $client->request('GET', '/room/report/' . $room->getId());
        $this->assertEquals(
            1,
            $crawler->filter('.modal-content:contains("Europe/Berlin")')->count()
        );

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
            $crawler->filter('.online')->count()
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

        $status = $room->getRoomstatuses()->toArray()[0];
        $roomstart = $status->getRoomCreatedAtwithTimeZone($testUser);
        $roomEnd = $status->getDestroyedAtwithTimeZone($testUser);
        $this->assertEquals(
            1,
            $crawler->filter('.statusOpeningDate:contains("' . $status->getRoomCreatedAtwithTimeZone($testUser)->format('H:i:s') . '")')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('.statusOpeningDate:contains("' . $status->getRoomCreatedAtwithTimeZone($testUser)->format('H:i:s') . '")')->count()
        );


        $status = $room->getRoomstatuses()->toArray()[1];
        $roomstart = $status->getRoomCreatedAtwithTimeZone($testUser);
        $roomEnd = $status->getDestroyedAtwithTimeZone($testUser);

        $this->assertEquals(
            1,
            $crawler->filter('.statusOpeningDate:contains("' . $roomstart->format('H:i:s') . '")')->count()
        );

        $this->assertEquals(
            1,
            $crawler->filter('.timelineENdRoom:contains("' . $roomEnd->format('H:i:s') . '")')->count()
        );

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.modal-header', 'Protokoll');
    }
}
