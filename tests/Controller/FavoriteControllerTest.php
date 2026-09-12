<?php

namespace App\Tests\Controller;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FavoriteControllerTest extends WebTestCase
{
    public function testToggleAddsRoomToFavorites(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['uidReal' => '9876543210']);
        $client->loginUser($user);

        $client->request('GET', '/room/favorite/toggle?uid=9876543210');

        $this->assertResponseRedirects('/room/dashboard');
        $updated = $userRepo->findOneBy(['email' => 'test@local.de']);
        $this->assertTrue($updated->getFavorites()->contains($room));
    }

    public function testToggleRemovesRoomFromFavorites(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['uidReal' => '9876543210']);
        $user->addFavorite($room);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($user);
        $em->flush();
        $client->loginUser($user);

        $client->request('GET', '/room/favorite/toggle?uid=9876543210');

        $this->assertResponseRedirects('/room/dashboard');
        $updated = $userRepo->findOneBy(['email' => 'test@local.de']);
        $this->assertFalse($updated->getFavorites()->contains($room));
    }

    public function testToggleRejectsUserNotInRoom(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $outsider = $userRepo->findOneBy(['email' => 'test@local4.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['uidReal' => '9876543210']);
        $client->loginUser($outsider);

        $client->request('GET', '/room/favorite/toggle?uid=9876543210');

        $this->assertResponseRedirects('/room/dashboard');
        $this->assertNotEmpty($client->getRequest()->getSession()->getFlashBag()->get('danger'));
        $updated = $userRepo->findOneBy(['email' => 'test@local4.de']);
        $this->assertFalse($updated->getFavorites()->contains($room));
    }
}
