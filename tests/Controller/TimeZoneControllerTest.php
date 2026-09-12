<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TimeZoneControllerTest extends WebTestCase
{
    public function testIndexRendersTimeZoneForm(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/timezone/change');

        $this->assertResponseIsSuccessful();
    }

    public function testIndexRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/room/timezone/change');

        $this->assertResponseRedirects();
    }

    public function testSavePersistsTimeZoneAndRedirectsToDashboard(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/room/timezone/change');
        $form = $crawler->filter('form')->form();
        $name = array_key_first($form->getPhpValues());
        $form[$name . '[timeZone]'] = 'Europe/Berlin';
        $client->submit($form);

        $this->assertResponseRedirects('/room/dashboard');
        $updated = self::getContainer()->get(UserRepository::class)->find($user->getId());
        $this->assertSame('Europe/Berlin', $updated->getTimeZone());
    }
}
