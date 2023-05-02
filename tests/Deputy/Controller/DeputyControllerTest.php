<?php

namespace App\Tests\Deputy\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DeputyControllerTest extends WebTestCase
{
    public function testUserIsInAdressbook(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $master = $userRepo->findOneBy(['email' => 'test@local.de']);
        $deputy = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($master);
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertEquals(0, $crawler->filter('.isDeputy')->count());
        $crawler = $client->request('GET', '/room/deputy/toggle/' . $deputy->getUid());
        self::assertResponseRedirects('/room/dashboard');
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertEquals(1, $crawler->filter('.isDeputy')->count());
        self::assertEquals(1, $crawler->filter('.snackbar:contains("Vertreter erfolgreich hinzugefügt.")')->count());
        $crawler = $client->request('GET', '/room/deputy/toggle/' . $deputy->getUid());
        self::assertResponseRedirects('/room/dashboard');
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertEquals(0, $crawler->filter('.isDeputy')->count());
        self::assertEquals(1, $crawler->filter('.snackbar:contains("Vertreter erfolgreich entfernt.")')->count());
    }
    public function testNotInAdressook(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $master = $userRepo->findOneBy(['email' => 'test@local.de']);
        $deputy = $userRepo->findOneBy(['email' => 'test@local4.de']);
        $client->loginUser($master);
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertEquals(0, $crawler->filter('.isDeputy')->count());
        $crawler = $client->request('GET', '/room/deputy/toggle/' . $deputy->getUid());
        self::assertResponseRedirects('/room/dashboard');
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertEquals(0, $crawler->filter('.isDeputy')->count());
        self::assertEquals(1, $crawler->filter('.snackbar:contains("Diese Aktion ist nicht erlaubt.")')->count());
    }
}
