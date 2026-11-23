<?php

namespace App\Tests\Controller;

use App\Entity\RoomsUser;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ChangeOwnershipControllerTest extends WebTestCase
{
    public function testIndexTransfersOwnership(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $owner = $userRepo->findOneBy(['email' => 'test@local.de']);
        $newOwner = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $client->loginUser($owner);

        $permission = new RoomsUser();
        $permission->setRoom($room);
        $permission->setUser($newOwner);
        $permission->setModerator(true);
        $em->persist($permission);
        $em->flush();

        $client->request('GET', '/room/ownership/' . $newOwner->getId() . '/' . $room->getId());

        $this->assertResponseRedirects('/room/dashboard');
        $em->refresh($room);
        $this->assertSame($newOwner->getId(), $room->getModerator()->getId());
    }

    public function testIndexWithoutPermissionDoesNotTransferOwnership(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $owner = $userRepo->findOneBy(['email' => 'test@local.de']);
        $newOwner = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $client->loginUser($owner);

        $client->request('GET', '/room/ownership/' . $newOwner->getId() . '/' . $room->getId());

        $this->assertResponseRedirects('/room/dashboard');
        self::getContainer()->get(EntityManagerInterface::class)->refresh($room);
        $this->assertSame($owner->getId(), $room->getModerator()->getId());
    }
}
