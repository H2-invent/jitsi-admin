<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdressbookFavoriteControllerTest extends WebTestCase
{
    public function testIndexAddsFavoriteAndRedirectsToDashboard(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $favorite = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/adressbook/favorite/' . $favorite->getUid());

        $this->assertResponseRedirects('/room/dashboard');
        $updated = $userRepo->findOneBy(['email' => 'test@local.de']);
        $this->assertTrue($updated->getAdressbookFavorites()->contains($favorite));
        $this->assertNotEmpty($client->getRequest()->getSession()->getFlashBag()->get('success'));
    }

    public function testIndexRemovesFavoriteWhenAlreadyFavorite(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $favorite = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $user->addAdressbookFavorite($favorite);
        $em = self::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        $em->persist($user);
        $em->flush();
        $client->loginUser($user);

        $client->request('GET', '/room/adressbook/favorite/' . $favorite->getUid());

        $this->assertResponseRedirects('/room/dashboard');
        $updated = $userRepo->findOneBy(['email' => 'test@local.de']);
        $this->assertFalse($updated->getAdressbookFavorites()->contains($favorite));
    }

    public function testIndexUnknownUserRedirectsWithDangerFlash(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/adressbook/favorite/unknown-uid');

        $this->assertResponseRedirects('/room/dashboard');
        $this->assertNotEmpty($client->getRequest()->getSession()->getFlashBag()->get('danger'));
    }

    public function testFavoriteAjaxAddsFavorite(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $favorite = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($user);

        $client->request('POST', '/room/adressbook/favorite-ajax/' . $favorite->getUid());

        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString('{"ok":true}', $client->getResponse()->getContent());
        $updated = $userRepo->findOneBy(['email' => 'test@local.de']);
        $this->assertTrue($updated->getAdressbookFavorites()->contains($favorite));
    }

    public function testFavoriteAjaxUnknownUserReturnsNotFound(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('POST', '/room/adressbook/favorite-ajax/unknown-uid');

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }
}
