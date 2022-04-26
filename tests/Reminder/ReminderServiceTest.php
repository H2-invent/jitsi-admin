<?php

namespace App\Tests\Reminder;

use App\Repository\NotificationRepository;
use App\Service\ReminderService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReminderServiceTest extends WebTestCase
{
    public function testHasNotification(): void
    {
        $client = static::createClient();
        $reminderTest = self::$container->get(ReminderService::class);
        $res = $reminderTest->sendReminder();
        $this->assertEquals(10, $res['Konferenzen']);
        $this->assertEquals(30, $res['Emails']);
        $this->assertEquals('Cron ok', $res['hinweis']);
        $this->assertEquals(false, $res['error']);
    }
}
