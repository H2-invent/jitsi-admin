<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReminderLizenseControllerTest extends WebTestCase
{
    public function testIndexRejectsWrongToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/reminder/lizense?token=wrong');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertSame('Token fehlerhaft', $data['hinweis']);
    }

    public function testIndexAcceptsValidToken(): void
    {
        $client = static::createClient();
        $token = self::getContainer()->getParameter('cronToken');
        $client->request('GET', '/reminder/lizense?token=' . $token);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['error']);
        $this->assertArrayHasKey('amount', $data);
    }
}
