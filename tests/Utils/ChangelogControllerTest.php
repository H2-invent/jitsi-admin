<?php

namespace App\Tests\Utils;

use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ChangelogControllerTest extends WebTestCase
{
use RefreshDatabaseTrait;
    public function testSomething(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/changelog');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.joinPageHeader', 'Changelog');
    }
}
