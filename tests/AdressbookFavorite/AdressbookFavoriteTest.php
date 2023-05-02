<?php

namespace App\Tests\AdressbookFavorite;

use App\Entity\User;
use App\Exceptions\UserAlreadyAdressbookFavoriteException;
use App\Exceptions\UserNotInAdressbookException;
use App\Repository\UserRepository;
use App\Service\adressbookFavoriteService\AdressbookFavoriteService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

class AdressbookFavoriteTest extends KernelTestCase
{
    public function testAddUserNotInAdressbook(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $adresbookService = self::getContainer()->get(AdressbookFavoriteService::class);
        $user1 = new User();
        $user2 = new User();
        self::expectException(UserNotInAdressbookException::class);
        $adresbookService->addFavorite($user1, $user2);
    }
    public function testAddUseralreadFavorite(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $adresbookService = self::getContainer()->get(AdressbookFavoriteService::class);
        $user1 = new User();

        $user2 = new User();
        $user1->addAddressbook($user2);
        $user1->addAdressbookFavorite($user2);
        self::expectException(UserAlreadyAdressbookFavoriteException::class);
        $adresbookService->addFavorite($user1, $user2);
    }
    public function testAddUserFavoriteSuccessfully(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $adresbookService = self::getContainer()->get(AdressbookFavoriteService::class);
        $userRepo = self::getContainer()->get(UserRepository::class);

        $user1 = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);

        self::assertTrue($adresbookService->addFavorite($user1, $user2));
        $user1 = $userRepo->findOneBy(['email' => 'test@local.de']);
        self::assertEquals(1, sizeof($user1->getAdressbookFavorites()));
        self::assertEquals($user2, $user1->getAdressbookFavorites()[0]);
    }
    public function testRemoveUserFavoriteSuccessfully(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $adresbookService = self::getContainer()->get(AdressbookFavoriteService::class);
        $userRepo = self::getContainer()->get(UserRepository::class);

        $user1 = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $user1->addAdressbookFavorite($user2);
        $user1 = $userRepo->findOneBy(['email' => 'test@local.de']);
        assertTrue($adresbookService->removeFavorite($user1, $user2));
        self::assertEquals(0, sizeof($user1->getAdressbookFavorites()));
    }

    public function testRemoveUserFavoriteFail(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $adresbookService = self::getContainer()->get(AdressbookFavoriteService::class);
        $userRepo = self::getContainer()->get(UserRepository::class);

        $user1 = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        assertFalse($adresbookService->removeFavorite($user1, $user2));
        self::assertEquals(0, sizeof($user1->getAdressbookFavorites()));
    }

    public function testAddUserFavoriteIISuccessfully(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $adresbookService = self::getContainer()->get(AdressbookFavoriteService::class);
        $userRepo = self::getContainer()->get(UserRepository::class);

        $user1 = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $res = $adresbookService->userFavorite($user1, $user2);
        self::assertEquals(['success', 'Sie haben Test2, 1234, User2, Test2 erfolgreich als Favorit hinzugefügt.'], $res);
    }
    public function testAddUserFavoriteIIFail(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $adresbookService = self::getContainer()->get(AdressbookFavoriteService::class);
        $userRepo = self::getContainer()->get(UserRepository::class);

        $user1 = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $user1->removeAddressbook($user2);
        $res = $adresbookService->userFavorite($user1, $user2);
        self::assertEquals(['danger', 'Der Kontakt konnte nicht als Favorit hinzugefügt werden.'], $res);
    }
    public function testRemoveUserFavoriteIISuccessfully(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $adresbookService = self::getContainer()->get(AdressbookFavoriteService::class);
        $userRepo = self::getContainer()->get(UserRepository::class);

        $user1 = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $user1->addAdressbookFavorite($user2);
        $user1 = $userRepo->findOneBy(['email' => 'test@local.de']);
        self::assertEquals($user2, $user1->getAdressbookFavorites()[0]);
        self::assertEquals(1, sizeof($user1->getAdressbookFavorites()));
        $res = $adresbookService->userFavorite($user1, $user2);
        self::assertEquals(['success', 'Sie haben Test2, 1234, User2, Test2 als Favorit entfernt.'], $res);
        self::assertEquals(0, sizeof($user1->getAdressbookFavorites()));
    }
}
