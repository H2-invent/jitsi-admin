<?php

namespace App\Tests\Favorites;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\FavoriteService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FavoriteServiceTest extends KernelTestCase
{
    public function testCorrectUser(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $favoriteService = $this->getContainer()->get(FavoriteService::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $res = $favoriteService->changeFavorite($user, $room);
        $this->assertTrue($res);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $this->assertTrue(in_array($room, $user->getFavorites()->toArray()));
        $res = $favoriteService->changeFavorite($user, $room);
        $this->assertTrue($res);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $this->assertFalse(in_array($room, $user->getFavorites()->toArray()));

        $user = $userRepo->findOneBy(['email' => 'test@local4.de']);
        $res = $favoriteService->changeFavorite($user, $room);
        $this->assertFalse($res);
    }
    public function testWrongtUser(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $favoriteService = $this->getContainer()->get(FavoriteService::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local4.de']);
        $res = $favoriteService->changeFavorite($user, $room);
        $this->assertFalse($res);
    }
    public function testcleanFavorites(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $favoriteService = $this->getContainer()->get(FavoriteService::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'Room Yesterday']);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $res = $favoriteService->changeFavorite($user, $room);
        $this->assertTrue($res);
        $this->assertTrue(in_array($room, $user->getFavorites()->toArray()));
        $favoriteService->cleanFavorites($user);
        $this->assertFalse(in_array($room, $user->getFavorites()->toArray()));
    }
}
