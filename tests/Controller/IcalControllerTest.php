<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class IcalControllerTest extends WebTestCase
{
    public function testIndexReturnsCalendar(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);

        $client->request('GET', '/ical/' . $user->getUid());

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('text/calendar', $client->getResponse()->headers->get('Content-Type'));
        $this->assertStringContainsString('BEGIN:VCALENDAR', $client->getResponse()->getContent());
    }
}
