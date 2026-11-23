<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecondEmailChangeControllerTest extends WebTestCase
{
    public function testIndexRendersSecondEmailForm(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/secondEmail/change');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testIndexRequiresLogin(): void
    {
        $client = static::createClient();

        $client->request('GET', '/room/secondEmail/change');

        $this->assertResponseRedirects();
    }

    public function testSaveStoresSecondEmailAndRedirectsToDashboard(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/room/secondEmail/change');
        $form = $crawler->filter('form')->form();
        $name = array_key_first($form->getPhpValues());
        $form[$name . '[secondEmail]'] = 'cc@example.com';
        $client->submit($form);

        $this->assertResponseRedirects('/room/dashboard');
        $updated = $userRepo->findOneBy(['email' => 'test@local.de']);
        $this->assertSame('cc@example.com', $updated->getSecondEmail());
        $this->assertNotEmpty($client->getRequest()->getSession()->getFlashBag()->get('success'));
    }

    public function testSaveRejectsInvalidSecondEmail(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/room/secondEmail/change');
        $form = $crawler->filter('form')->form();
        $name = array_key_first($form->getPhpValues());
        $form[$name . '[secondEmail]'] = 'not-an-email';
        $client->submit($form);

        $this->assertResponseRedirects('/room/dashboard');
        $updated = $userRepo->findOneBy(['email' => 'test@local.de']);
        $this->assertNotSame('not-an-email', $updated->getSecondEmail());
        $this->assertNotEmpty($client->getRequest()->getSession()->getFlashBag()->get('danger'));
    }
}
