<?php

namespace App\Tests\AdressbookFavorite;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AddressbookFavoriteControllerTest extends WebTestCase
{
    public function testSomething(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $crawler = $client->request('GET', '/');
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $user2 = $userRepository->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        self::assertEquals(0, $crawler->filter('.isAddressbookFavorite')->count());
        $crawler = $client->request('GET', '/room/adressbook/favorite/' . $user2->getUid());
        self::assertResponseRedirects('/room/dashboard');
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.snackbar', 'Sie haben Test2, 1234, User2, Test2 erfolgreich als Favorit hinzugefügt.');
        self::assertEquals(2, $crawler->filter('.isAddressbookFavorite')->count());
        $crawler = $client->request('GET', '/room/adressbook/favorite/' . $user2->getUid());
        self::assertResponseRedirects('/room/dashboard');
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.snackbar', 'Sie haben Test2, 1234, User2, Test2 als Favorit entfernt.');
        self::assertEquals(0, $crawler->filter('.isAddressbookFavorite')->count());
    }
}
