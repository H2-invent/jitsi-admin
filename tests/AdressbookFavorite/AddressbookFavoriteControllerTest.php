<?php

namespace App\Tests\AdressbookFavorite;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AddressbookFavoriteControllerTest extends WebTestCase
{
    /**
     * Returns the number of favourite contacts in the React address book bootstrap
     * state. The dashboard address book pane is React-owned, so favourite state is
     * bootstrapped as JSON (not rendered as server-side HTML).
     */
    private function favoriteCountInState($crawler): int
    {
        $node = $crawler->filter('#addressbook-state');
        if ($node->count() === 0) {
            return 0;
        }
        $state = json_decode($node->text(), true);
        $count = 0;
        foreach (($state['contacts'] ?? []) as $contact) {
            if (!empty($contact['isFavorite'])) {
                $count++;
            }
        }
        return $count;
    }

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
        self::assertEquals(0, $this->favoriteCountInState($crawler));
        $crawler = $client->request('GET', '/room/adressbook/favorite/' . $user2->getUid());
        self::assertResponseRedirects('/room/dashboard');
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.snackbar', 'Sie haben Test2, 1234, User2, Test2 erfolgreich als Favorit hinzugefügt.');
        self::assertEquals(1, $this->favoriteCountInState($crawler));
        $crawler = $client->request('GET', '/room/adressbook/favorite/' . $user2->getUid());
        self::assertResponseRedirects('/room/dashboard');
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.snackbar', 'Sie haben Test2, 1234, User2, Test2 als Favorit entfernt.');
        self::assertEquals(0, $this->favoriteCountInState($crawler));
    }
}
