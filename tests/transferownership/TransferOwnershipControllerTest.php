<?php

namespace App\Tests\transferownership;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TransferOwnershipControllerTest extends WebTestCase
{
    public function testNewOwnerNotMOderator(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email'=>'test@local.de']);
        $newOwner = $userRepo->findOneBy(['email'=>'test@local2.de']);
        $client->loginUser($user);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name'=>'TestMeeting: 0']);

        $crawler = $client->request('GET', '/room/participant/add?room='.$room->getId());
        self::assertSelectorNotExists('#transferRoomTo'.$newOwner->getId());
    }
    public function testNewOwnerIsMOderator(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email'=>'test@local.de']);
        $newOwner = $userRepo->findOneBy(['email'=>'test@local2.de']);
        $client->loginUser($user);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name'=>'TestMeeting: 0']);
        $crawler = $client->request('GET', '/room/addModerator?room='.$room->getId().'&user='.$newOwner->getId());
        $crawler = $client->request('GET', '/room/participant/add?room='.$room->getId());
        self::assertSelectorExists('#transferRoomTo'.$newOwner->getId());
    }
    public function testNewOwnerhasNoKeycloakId(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email'=>'test@local.de']);
        $newOwner = $userRepo->findOneBy(['email'=>'test@local3.de']);
        $client->loginUser($user);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name'=>'TestMeeting: 0']);
        $crawler = $client->request('GET', '/room/addModerator?room='.$room->getId().'&user='.$newOwner->getId());
        $crawler = $client->request('GET', '/room/participant/add?room='.$room->getId());
        self::assertSelectorNotExists('#transferRoomTo'.$newOwner->getId());
    }
    public function testNewOwnerIsMOderatorTransform(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email'=>'test@local.de']);
        $newOwner = $userRepo->findOneBy(['email'=>'test@local2.de']);
        $client->loginUser($user);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name'=>'TestMeeting: 0']);
        $crawler = $client->request('GET', '/room/addModerator?room='.$room->getId().'&user='.$newOwner->getId());
        $crawler = $client->request('GET', '/room/ownership/'.$newOwner->getId().'/'.$room->getId());
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertSelectorTextContains('.innerOnce','Konferenz erfolgreich übertragen');
    }
    public function testNewOwnerIsNotMOderatorTransform(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email'=>'test@local.de']);
        $newOwner = $userRepo->findOneBy(['email'=>'test@local2.de']);
        $client->loginUser($user);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name'=>'TestMeeting: 0']);
        $crawler = $client->request('GET', '/room/ownership/'.$newOwner->getId().'/'.$room->getId());
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertSelectorTextContains('.innerOnce','Fehler. Die Konfernz konnte nicht übertragen werden.');
    }
    public function testNewOwnerIsNotOwnerOfRoom(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email'=>'test@local2.de']);
        $newOwner = $userRepo->findOneBy(['email'=>'test@local3.de']);
        $client->loginUser($user);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name'=>'TestMeeting: 0']);
        $crawler = $client->request('GET', '/room/ownership/'.$newOwner->getId().'/'.$room->getId());
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertSelectorTextContains('.innerOnce','Fehler. Die Konfernz konnte nicht übertragen werden.');
    }
    public function testNewOwnerIsNotKeycloakUserButBAckend(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email'=>'test@local.de']);
        $newOwner = $userRepo->findOneBy(['email'=>'test@local3.de']);
        $client->loginUser($user);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name'=>'TestMeeting: 0']);
        $crawler = $client->request('GET', '/room/addModerator?room='.$room->getId().'&user='.$newOwner->getId());
        $crawler = $client->request('GET', '/room/ownership/'.$newOwner->getId().'/'.$room->getId());
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertSelectorTextContains('.innerOnce','Fehler. Die Konfernz konnte nicht übertragen werden.');
    }
}
