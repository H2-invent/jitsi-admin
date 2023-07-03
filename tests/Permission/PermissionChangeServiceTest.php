<?php

namespace App\Tests\Permission;

use App\Entity\LobbyWaitungUser;
use App\Repository\LobbyWaitungUserRepository;
use App\Repository\RoomsRepository;
use App\Repository\RoomsUserRepository;
use App\Repository\UserRepository;
use App\Service\PermissionChangeService;
use App\Service\RoomAddService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PermissionChangeServiceTest extends KernelTestCase
{
    public function testchangeModarator(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $changePermissionService = self::getContainer()->get(PermissionChangeService::class);
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneBy(['email' => 'test@local2.de']);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $this->assertEquals(true, $changePermissionService->toggleModerator($room->getModerator(), $testUser, $room));
        $this->assertEquals(false, $changePermissionService->toggleModerator($testUser, $testUser, $room));
        $userRoomRepo = self::getContainer()->get(RoomsUserRepository::class);
        $userRoom = $userRoomRepo->findOneBy(['user' => $testUser, 'room' => $room]);
        $this->assertEquals(true, $userRoom->getModerator());
        $this->assertEquals(false, $userRoom->getLobbyModerator());
        $this->assertEquals(false, $userRoom->getPrivateMessage());
        $this->assertEquals(false, $userRoom->getShareDisplay());
        $this->assertEquals(true, $changePermissionService->toggleModerator($room->getModerator(), $testUser, $room));
        $this->assertEquals(false, $userRoom->getModerator());
        //$routerService = static::getContainer()->get('router');
        //$myCustomService = static::getContainer()->get(CustomService::class);
    }

    public function testchangeModaratorFromLDAPNotAllowed(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $changePermissionService = self::getContainer()->get(PermissionChangeService::class);
        $userRepository = static::getContainer()->get(UserRepository::class);
        $userAddService = self::getContainer()->get(RoomAddService::class);

        // retrieve the test user
        $testUser = $userRepository->findOneBy(['email' => 'ldapUser@local.de']);


        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);

        $userAddService->createParticipants('ldapUser@local.de', $room);

        $this->assertEquals(true, $changePermissionService->toggleModerator($room->getModerator(), $testUser, $room));
        $this->assertEquals(false, $changePermissionService->toggleModerator($testUser, $testUser, $room));
        $userRoomRepo = self::getContainer()->get(RoomsUserRepository::class);
        $userRoom = $userRoomRepo->findOneBy(['user' => $testUser, 'room' => $room]);
        $this->assertEquals($userRoom, $changePermissionService->toggleLobbyModerator($room->getModerator(), $testUser, $room));
        $this->assertEquals(false, $userRoom->getModerator());
        $this->assertEquals(false, $userRoom->getLobbyModerator());
        $this->assertEquals(false, $userRoom->getPrivateMessage());
        $this->assertEquals(false, $userRoom->getShareDisplay());
        $this->assertEquals(true, $changePermissionService->toggleModerator($room->getModerator(), $testUser, $room));
        $this->assertEquals($userRoom, $changePermissionService->toggleLobbyModerator($room->getModerator(), $testUser, $room));
        $this->assertEquals(false, $userRoom->getModerator());
        $this->assertEquals(false, $userRoom->getLobbyModerator());
        //$routerService = static::getContainer()->get('router');
        //$myCustomService = static::getContainer()->get(CustomService::class);
    }

    public function testchangeLobbyModerator(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $changePermissionService = self::getContainer()->get(PermissionChangeService::class);
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneBy(['email' => 'test@local2.de']);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $this->assertEquals(true, $changePermissionService->toggleLobbyModerator($room->getModerator(), $testUser, $room)->getLobbyModerator());
        $this->assertEquals(false, $changePermissionService->toggleLobbyModerator($testUser, $testUser, $room));
        $userRoomRepo = self::getContainer()->get(RoomsUserRepository::class);
        $userRoom = $userRoomRepo->findOneBy(['user' => $testUser, 'room' => $room]);
        $lobbyWaitingUSer = (new LobbyWaitungUser())->setRoom($room)->setUser($testUser)->setShowName('test123')->setType('a')->setUid('kjdshfkhds')->setCreatedAt(new \DateTime());
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($lobbyWaitingUSer);
        $em->flush();
        $lobbyWaitungRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        self::assertEquals(1, sizeof($lobbyWaitungRepo->findBy(['user' => $testUser, 'room' => $room])));
        $this->assertEquals(true, $userRoom->getLobbyModerator());
        $this->assertEquals(false, $userRoom->getModerator());
        $this->assertEquals(false, $userRoom->getPrivateMessage());
        $this->assertEquals(false, $userRoom->getShareDisplay());
        $this->assertNotNull($changePermissionService->toggleLobbyModerator($room->getModerator(), $testUser, $room));
        $this->assertEquals(false, $userRoom->getLobbyModerator());
        self::assertEquals(0, sizeof($lobbyWaitungRepo->findBy(['user' => $testUser, 'room' => $room])));
    }

    public function testchangeShareScreen(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $changePermissionService = self::getContainer()->get(PermissionChangeService::class);
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneBy(['email' => 'test@local2.de']);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $this->assertEquals(true, $changePermissionService->toggleShareScreen($room->getModerator(), $testUser, $room));
        $this->assertEquals(false, $changePermissionService->toggleLobbyModerator($testUser, $testUser, $room));
        $userRoomRepo = self::getContainer()->get(RoomsUserRepository::class);
        $userRoom = $userRoomRepo->findOneBy(['user' => $testUser, 'room' => $room]);
        $this->assertEquals(true, $userRoom->getShareDisplay());
        $this->assertEquals(false, $userRoom->getModerator());
        $this->assertEquals(false, $userRoom->getPrivateMessage());
        $this->assertEquals(false, $userRoom->getLobbyModerator());
        $this->assertEquals(true, $changePermissionService->toggleShareScreen($room->getModerator(), $testUser, $room));
        $this->assertEquals(false, $userRoom->getShareDisplay());
        //$routerService = static::getContainer()->get('router');
        //$myCustomService = static::getContainer()->get(CustomService::class);
    }

    public function testchangePrivateMessage(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $changePermissionService = self::getContainer()->get(PermissionChangeService::class);
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneBy(['email' => 'test@local2.de']);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $this->assertEquals(true, $changePermissionService->togglePrivateMessage($room->getModerator(), $testUser, $room));
        $this->assertEquals(false, $changePermissionService->togglePrivateMessage($testUser, $testUser, $room));
        $userRoomRepo = self::getContainer()->get(RoomsUserRepository::class);
        $userRoom = $userRoomRepo->findOneBy(['user' => $testUser, 'room' => $room]);
        $this->assertEquals(true, $userRoom->getPrivateMessage());
        $this->assertEquals(false, $userRoom->getModerator());
        $this->assertEquals(false, $userRoom->getShareDisplay());
        $this->assertEquals(false, $userRoom->getLobbyModerator());
        $this->assertEquals(true, $changePermissionService->togglePrivateMessage($room->getModerator(), $testUser, $room));
        $this->assertEquals(false, $userRoom->getPrivateMessage());
        //$routerService = static::getContainer()->get('router');
        //$myCustomService = static::getContainer()->get(CustomService::class);
    }
}
