<?php

namespace App\Tests\Reminder;

use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use App\Repository\NotificationRepository;
use App\Service\ReminderService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReminderServiceTest extends WebTestCase
{
use RefreshDatabaseTrait;
    public function testHasNotification(): void
    {
        $client = static::createClient();
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
        $reminderTest = self::getContainer()->get(ReminderService::class);
        $res = $reminderTest->sendReminder(['http://localhost:8000']);
        $this->assertEquals(5, $res['Konferenzen']);
        $this->assertEquals(15, $res['Emails']);
        $this->assertEquals('Cron ok', $res['hinweis']);
        $this->assertEquals(false, $res['error']);
    }
    public function testHasNotificationwithFilterMixed(): void
    {
        $client = static::createClient();
        $reminderTest = self::getContainer()->get(ReminderService::class);
        $res = $reminderTest->sendReminder([null, 'http://localhost:8000']);
        $this->assertEquals(10, $res['Konferenzen']);
        $this->assertEquals(30, $res['Emails']);
        $this->assertEquals('Cron ok', $res['hinweis']);
        $this->assertEquals(false, $res['error']);
    }
}
