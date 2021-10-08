<?php

namespace App\Tests;

use App\Repository\NotificationRepository;
use App\Repository\RoomsRepository;
use App\Service\ReminderService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
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
        $reminderRepo = self::$container->get(NotificationRepository::class);
        $this->assertEquals(30, sizeof($reminderRepo->findAll()));
    }
}
