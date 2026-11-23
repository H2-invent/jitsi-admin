<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CronControllerTest extends WebTestCase
{
    public function testUpdateCronAkademieRejectsWrongToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/cron/remember?token=wrong');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertSame('Token fehlerhaft', $data['hinweis']);
    }

    public function testUpdateCronAkademieAcceptsValidToken(): void
    {
        $client = static::createClient();
        $token = self::getContainer()->getParameter('cronToken');
        $client->request('GET', '/cron/remember?token=' . $token);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['error']);
        $this->assertSame('Cron ok', $data['hinweis']);
    }

    public function testUpdateCronRunRejectsWrongToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/cron/run?token=wrong');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertSame('Token fehlerhaft', $data['hinweis']);
    }
}
