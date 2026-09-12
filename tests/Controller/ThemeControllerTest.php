<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ThemeControllerTest extends WebTestCase
{
    public function testShowThemesRendersOverview(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/theme/overview');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Themes');
    }

    public function testShowThemesRequiresLogin(): void
    {
        $client = static::createClient();

        $client->request('GET', '/room/theme/overview');

        $this->assertResponseRedirects();
    }
}
