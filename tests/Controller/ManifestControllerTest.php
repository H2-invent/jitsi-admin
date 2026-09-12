<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ManifestControllerTest extends WebTestCase
{
    public function testIndexReturnsManifest(): void
    {
        $client = static::createClient();
        $client->request('GET', '/site.webmanifest');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('start_url', $data);
        $this->assertArrayHasKey('icons', $data);
        $this->assertSame('/room/dashboard', $data['start_url']);
        $this->assertSame('standalone', $data['display']);
    }
}
