<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HealthCheckControllerTest extends WebTestCase
{
    public function testIndexReturnsHealthyJson(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health/check');

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        $this->assertSame(['health' => true], json_decode($client->getResponse()->getContent(), true));
    }
}
