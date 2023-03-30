<?php

namespace App\Tests\Utils;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ChangelogControllerTest extends WebTestCase
{
    public function testSomething(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/changelog');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.joinPageHeader', 'Changelog');
    }
}
