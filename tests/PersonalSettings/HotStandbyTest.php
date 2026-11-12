<?php

namespace App\Tests\PersonalSettings;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class HotStandbyTest extends WebTestCase
{
    protected function tearDown(): void
    {
        $this->setHotStandbyFlag('1');
        parent::tearDown();
    }

    public function testSuccess(): void
    {
        $this->setHotStandbyFlag('1');
        $client = static::createClient();
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $translator = self::getContainer()->get(TranslatorInterface::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);
        self::assertFalse($user->isHotstandbyEnabled());

        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $form = $crawler->filter('form[action="/room/hotstandby/save"]')->form();
        $form['hot_standby[hotstandbyEnabled]']->tick();

        $client->submit($form);
        self::assertResponseRedirects($urlGen->generate('dashboard'));

        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        self::assertEquals(
            $translator->trans('Hot Standby Einstellung erfolgreich gespeichert'),
            $crawler->filter('.snackbar')->text()
        );

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        self::assertTrue($user->isHotstandbyEnabled());
    }

    public function testDisableAgain(): void
    {
        $this->setHotStandbyFlag('1');
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user->setHotstandbyEnabled(true);
        self::getContainer()->get('doctrine')->getManager()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/room/dashboard');
        $form = $crawler->filter('form[action="/room/hotstandby/save"]')->form();
        $form['hot_standby[hotstandbyEnabled]']->untick();
        $client->submit($form);
        self::assertResponseRedirects();

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        self::assertFalse($user->isHotstandbyEnabled());
    }

    public function testFeatureDisabled(): void
    {
        $this->setHotStandbyFlag('0');
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('#collapseHotStandby');

        $client->request('POST', '/room/hotstandby/save', ['hot_standby' => ['hotstandbyEnabled' => '1']]);
        self::assertResponseStatusCodeSame(403);

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        self::assertFalse($user->isHotstandbyEnabled());
    }

    private function setHotStandbyFlag(string $value): void
    {
        $_ENV['LAF_SHOW_HOTSTANDBY'] = $value;
        $_SERVER['LAF_SHOW_HOTSTANDBY'] = $value;
        putenv('LAF_SHOW_HOTSTANDBY=' . $value);
    }
}
