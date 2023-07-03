<?php

namespace App\Tests\PersonalSettings;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecondEmailTest extends WebTestCase
{
    public function testSucess(): void
    {

        $client = static::createClient();
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $translator = self::getContainer()->get(TranslatorInterface::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/room/secondEmail/change');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.modal-header', 'Persönliche Einstellungen');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['second_email[secondEmail]'] = 'testcc@test.de, test@cc.de';

        $client->submit($form);
        $this->assertResponseRedirects($urlGen->generate('dashboard'));
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar')->text();
        self::assertEquals($flashMessage, $translator->trans('CC-E-Mails erfolgreich geändert auf: {secondEmails}'));


        self::assertEquals('testcc@test.de, test@cc.de', $user->getSecondEmail());
    }
    public function testInvalidEmail(): void
    {

        $client = static::createClient();

        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $translator = self::getContainer()->get(TranslatorInterface::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/room/secondEmail/change');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.modal-header', 'Persönliche Einstellungen');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['second_email[secondEmail]'] = 'testcc@test, test@cc.de';

        $client->submit($form);
        $this->assertResponseRedirects($urlGen->generate('dashboard'));
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar')->text();
        self::assertEquals($flashMessage, 'Ungültige E-Mail. Bitte überprüfen Sie Ihre E-Mail-Adresse.');

        self::assertEquals(null, $user->getSecondEmail());
    }
    public function testCombined(): void
    {

        $client = static::createClient();
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $translator = self::getContainer()->get(TranslatorInterface::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);
        $crawler = $client->request('GET', '/room/secondEmail/change');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.modal-header', 'Persönliche Einstellungen');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['second_email[secondEmail]'] = 'testcc@test.de, test@cc.de';

        $client->submit($form);
        $this->assertResponseRedirects($urlGen->generate('dashboard'));
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar .bg-success')->text();
        self::assertEquals($flashMessage, 'Persönliche Einstellungen erfolgreich geändert.');


        self::assertEquals('testcc@test.de, test@cc.de', $user->getSecondEmail());

        $crawler = $client->request('GET', '/room/secondEmail/change');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.modal-header', 'Persönliche Einstellungen');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['second_email[secondEmail]'] = 'testcc@test, test@cc.de';

        $client->submit($form);
        $this->assertResponseRedirects($urlGen->generate('dashboard'));

        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar .bg-danger')->text();
        self::assertEquals($flashMessage, 'Ungültige E-Mail. Bitte überprüfen Sie Ihre E-Mail-Adresse.');

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        self::assertEquals('testcc@test.de, test@cc.de', $user->getSecondEmail());
    }
}
