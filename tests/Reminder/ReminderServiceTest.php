<?php

namespace App\Tests\Reminder;

use App\Entity\Rooms;
use App\Repository\RoomsRepository;
use App\Service\ReminderService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReminderServiceTest extends WebTestCase
{
    /**
     * The reminder cron sends mails for rooms that start within the next 10 minutes. The
     * fixture rooms start relative to the fixture load time, so the exact set of rooms in
     * that window changes as time passes. To keep the tests deterministic, move every room
     * out of the window and pin exactly the rooms the assertions expect back into it:
     * 5 rooms with hostUrl http://localhost:8000 and 5 rooms with hostUrl NULL (each with
     * the fixture's 3 participants).
     */
    private function prepareReminderData(): void
    {
        $em = self::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $past = (clone $now)->modify('-1 day');
        $soon = (clone $now)->modify('+5 minutes');
        $soonEnd = (clone $soon)->modify('+60 minutes');

        foreach ($roomRepo->findAll() as $room) {
            $room->setStartUtc($past);
            $room->setEndDateUtc($past);
            $em->persist($room);
        }

        $names = array_merge(
            array_map(static fn (int $i): string => 'TestMeeting: ' . $i, range(0, 4)),
            array_map(static fn (int $i): string => 'TestMeeting_Amerika: ' . $i, range(0, 4))
        );
        foreach ($names as $name) {
            $room = $roomRepo->findOneBy(['name' => $name]);
            $this->assertNotNull($room, 'Fixture room "' . $name . '" must exist');
            $room->setStartUtc($soon);
            $room->setEndDateUtc($soonEnd);
            $em->persist($room);
        }

        $em->flush();
    }

    public function testHasNotification(): void
    {
        $client = static::createClient();
        $this->prepareReminderData();
        $reminderTest = self::getContainer()->get(ReminderService::class);
        $res = $reminderTest->sendReminder(null);
        $this->assertEquals(10, $res['Konferenzen']);
        $this->assertEquals(30, $res['Emails']);
        $this->assertEquals('Cron ok', $res['hinweis']);
        $this->assertEquals(false, $res['error']);
    }
    public function testHasNotificationwithFilter(): void
    {
        $client = static::createClient();
        $this->prepareReminderData();
        $reminderTest = self::getContainer()->get(ReminderService::class);
        $res = $reminderTest->sendReminder([null]);
        $this->assertEquals(5, $res['Konferenzen']);
        $this->assertEquals(15, $res['Emails']);
        $this->assertEquals('Cron ok', $res['hinweis']);
        $this->assertEquals(false, $res['error']);
    }
    public function testHasNotificationwithFilterLocalhost(): void
    {
        $client = static::createClient();
        $this->prepareReminderData();
        $reminderTest = self::getContainer()->get(ReminderService::class);
        $res = $reminderTest->sendReminder(['http://localhost:8000']);
        $this->assertEquals(5, $res['Konferenzen']);
        $this->assertEquals(15, $res['Emails']);
        $this->assertEquals('Cron ok', $res['hinweis']);
        $this->assertEquals(false, $res['error']);
    }

}
