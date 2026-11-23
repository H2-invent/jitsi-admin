<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TermsAndConditionsControllerTest extends WebTestCase
{
    public function testIndexRedirectsWhenTermsDisabled(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/terms/and/conditions');

        $this->assertResponseRedirects('/room/dashboard');
    }

    public function testAcceptTermsRedirectsToDashboard(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/terms/and/conditions/accept');

        $this->assertResponseRedirects('/room/dashboard');
        self::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class)->refresh($user);
        $this->assertTrue($user->isAcceptTermsAndConditions());
    }
}
