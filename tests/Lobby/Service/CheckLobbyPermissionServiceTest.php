<?php

namespace App\Tests\Lobby\Service;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\CheckLobbyPermissionService;
use App\Service\PermissionChangeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CheckLobbyPermissionServiceTest extends KernelTestCase
{
    public function testLobbyPermissionService(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());

        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $room->setLobby(true);
        $room->setPersistantRoom(true);
        $room->setStart(null);
        $room->setEnddate(null);
        $room->setTotalOpenRooms(true);
        $manager->persist($room);
        $manager->flush();


        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local3.de']);

        $permissionCheckService = self::getContainer()->get(CheckLobbyPermissionService::class);
        self::assertFalse($permissionCheckService->checkPermissions($room, $user));

        $permissionService = self::getContainer()->get(PermissionChangeService::class);
        $per = $permissionService->toggleLobbyModerator($room->getModerator(), $user, $room);
        self::assertEquals($user, $per->getUser());
        self::assertEquals($room, $per->getRoom());
        $manager->persist($per);
        $manager->flush();
        $user->addRoomsAttributes($per);
        $room->addUserAttribute($per);
        self::assertTrue($permissionCheckService->checkPermissions($room, $room->getModerator()));
        self::assertTrue($permissionCheckService->checkPermissions($room, $user));
    }
}
