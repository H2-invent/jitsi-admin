<?php

namespace App\Tests\PersonalSettings;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
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
        $user = $userRepo->findOneBy(array('email'=>'test@local.de'));
        $client->loginUser($user);

        $crawler = $client->request('GET', '/room/secondEmail/change');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.modal-header', 'Persönliche Einstellungen');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['second_email[secondEmail]'] = 'testcc@test.de, test@cc.de';

        $client->submit($form);
        $this->assertResponseRedirects($urlGen->generate('dashboard'));
        $user = $userRepo->findOneBy(array('email'=>'test@local.de'));
        $session = $client->getContainer()->get('session');
        $flash = $session->getBag('flashes')->all();
        self::assertEquals($flash['success'][0],$translator->trans('CC-E-Mails erfolgreich geändert auf: {secondEmails}'));

        self::assertEquals('testcc@test.de, test@cc.de',$user->getSecondEmail());

    }
    public function testInvalidEmail(): void
    {

        $client = static::createClient();
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $translator = self::getContainer()->get(TranslatorInterface::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local.de'));
        $client->loginUser($user);

        $crawler = $client->request('GET', '/room/secondEmail/change');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.modal-header', 'Persönliche Einstellungen');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['second_email[secondEmail]'] = 'testcc@test, test@cc.de';

        $client->submit($form);
        $this->assertResponseRedirects($urlGen->generate('dashboard'));
        $session = $client->getContainer()->get('session');
        $flash = $session->getBag('flashes')->all();
        self::assertEquals($flash['danger'][0],'Ungültige E-Mail. Bitte überprüfen Sie Ihre E-Mail-Adresse.');

        self::assertEquals(null,$user->getSecondEmail());
    }
    public function testCombined(): void
    {

        $client = static::createClient();
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $translator = self::getContainer()->get(TranslatorInterface::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local.de'));
        $client->loginUser($user);
        $crawler = $client->request('GET', '/room/secondEmail/change');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.modal-header', 'Persönliche Einstellungen');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['second_email[secondEmail]'] = 'testcc@test.de, test@cc.de';

        $client->submit($form);
        $this->assertResponseRedirects($urlGen->generate('dashboard'));
        $user = $userRepo->findOneBy(array('email'=>'test@local.de'));
        $session = $client->getContainer()->get('session');
        $flash = $session->getBag('flashes')->all();
        self::assertEquals($flash['success'][0],$translator->trans('CC-E-Mails erfolgreich geändert auf: {secondEmails}'));
        self::assertEquals('testcc@test.de, test@cc.de',$user->getSecondEmail());

        $crawler = $client->request('GET', '/room/secondEmail/change');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.modal-header', 'Persönliche Einstellungen');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['second_email[secondEmail]'] = 'testcc@test, test@cc.de';

        $client->submit($form);
        $this->assertResponseRedirects($urlGen->generate('dashboard'));
        $session = $client->getContainer()->get('session');
        $flash = $session->getBag('flashes')->all();
        self::assertEquals($flash['danger'][0],'Ungültige E-Mail. Bitte überprüfen Sie Ihre E-Mail-Adresse.');
        $user = $userRepo->findOneBy(array('email'=>'test@local.de'));
        self::assertEquals('testcc@test.de, test@cc.de',$user->getSecondEmail());
    }
}
