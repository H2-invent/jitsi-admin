<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileImageChangeControllerTest extends WebTestCase
{
    public function testIndexRendersProfileImageForm(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/profileImage/change');

        $this->assertResponseIsSuccessful();
    }

    public function testIndexRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/room/profileImage/change');

        $this->assertResponseRedirects();
    }

    public function testSaveRedirectsToDashboard(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/room/profileImage/change');
        $form = $crawler->filter('form')->form();
        $client->submit($form);

        $this->assertResponseRedirects('/room/dashboard');
        $this->assertNotEmpty($client->getRequest()->getSession()->getFlashBag()->get('success'));
        $this->assertEmpty($client->getRequest()->getSession()->getFlashBag()->get('danger'));
    }
}
