<?php

namespace App\Tests;

use App\Entity\RoomsUser;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\StartMeetingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StartServiceTest extends KernelTestCase
{
    public function testUserIsOrganizer(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $startService = self::getContainer()->get(StartMeetingService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 1'));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local.de'));
        self::assertEquals('jitsi-meet://meet.jit.si2/123456781?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzNDU2NzgxIiwiY29udGV4dCI6eyJ1c2VyIjp7Im5hbWUiOiJVc2VyLCBUZXN0LCB0ZXN0QGxvY2FsLmRlIn19LCJtb2RlcmF0b3IiOnRydWV9.rgoK2HJlevbuRz1M3cIrkmJSARhQ6addjyaBG6zP4qU#config.subject=%22TestMeeting: 1%22', $startService->startMeeting($room,$user,'a'));
        self::assertEquals('https://meet.jit.si2/123456781?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzNDU2NzgxIiwiY29udGV4dCI6eyJ1c2VyIjp7Im5hbWUiOiJVc2VyLCBUZXN0LCB0ZXN0QGxvY2FsLmRlIn19LCJtb2RlcmF0b3IiOnRydWV9.rgoK2HJlevbuRz1M3cIrkmJSARhQ6addjyaBG6zP4qU#config.subject=%22TestMeeting: 1%22', $startService->startMeeting($room,$user,'b'));
        //$routerService = static::getContainer()->get('router');
        //$myCustomService = static::getContainer()->get(CustomService::class);
    }
    public function testUserIsOrganizeraAndFixedRoom(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $startService = self::getContainer()->get(StartMeetingService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'This Room has no participants and fixed room'));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local.de'));
        self::assertEquals('jitsi-meet://meet.jit.si2/561d6f51s6f?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiNTYxZDZmNTFzNmYiLCJjb250ZXh0Ijp7InVzZXIiOnsibmFtZSI6IlVzZXIsIFRlc3QsIHRlc3RAbG9jYWwuZGUifX0sIm1vZGVyYXRvciI6dHJ1ZX0.QGhyBYZF_hkMZu1tRQF7mfGv1aLV9Ewp21vgd4cGDto#config.subject=%22This Room has no participants and fixed room%22', $startService->startMeeting($room,$user,'a'));
        self::assertEquals('https://meet.jit.si2/561d6f51s6f?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiNTYxZDZmNTFzNmYiLCJjb250ZXh0Ijp7InVzZXIiOnsibmFtZSI6IlVzZXIsIFRlc3QsIHRlc3RAbG9jYWwuZGUifX0sIm1vZGVyYXRvciI6dHJ1ZX0.QGhyBYZF_hkMZu1tRQF7mfGv1aLV9Ewp21vgd4cGDto#config.subject=%22This Room has no participants and fixed room%22', $startService->startMeeting($room,$user,'b'));
        //$routerService = static::getContainer()->get('router');
        //$myCustomService = static::getContainer()->get(CustomService::class);
    }
    public function testRoomHasLobbyuserisOrganizer(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $startService = self::getContainer()->get(StartMeetingService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'This is a room with Lobby'));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local.de'));
        self::assertEquals('/room/lobby/moderator/a/561ghj984ssdfdf', $startService->startMeeting($room,$user,'a'));
        self::assertEquals('/room/lobby/moderator/b/561ghj984ssdfdf', $startService->startMeeting($room,$user,'b'));
        //$routerService = static::getContainer()->get('router');
        //$myCustomService = static::getContainer()->get(CustomService::class);
    }
    public function testRoomHasLobbyuserisnoLobbyMOderator(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $startService = self::getContainer()->get(StartMeetingService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'This is a room with Lobby'));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local2.de'));
        self::assertEquals('/lobby/participants/a/561ghj984ssdfdf/kljlsdkjflkjdslfjsdlkjsdflkj', $startService->startMeeting($room,$user,'a'));
        self::assertEquals('/lobby/participants/b/561ghj984ssdfdf/kljlsdkjflkjdslfjsdlkjsdflkj', $startService->startMeeting($room,$user,'b'));
        //$routerService = static::getContainer()->get('router');
        //$myCustomService = static::getContainer()->get(CustomService::class);
    }
    public function testRoomHasLobbyuserisLobbyMOderator(): void
    {
        $kernel = self::bootKernel();
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $startService = self::getContainer()->get(StartMeetingService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'This is a room with Lobby'));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local2.de'));
        $permission = new RoomsUser();
        $permission->setRoom($room);
        $permission->setUser($user);
        $permission->setLobbyModerator(true);
        $manager->persist($permission);
        $manager->flush();
        self::assertEquals('/room/lobby/moderator/a/561ghj984ssdfdf', $startService->startMeeting($room,$user,'a'));
        self::assertEquals('/room/lobby/moderator/b/561ghj984ssdfdf', $startService->startMeeting($room,$user,'b'));
        //$routerService = static::getContainer()->get('router');
        //$myCustomService = static::getContainer()->get(CustomService::class);
    }
    public function testRoomisToEarly(): void
    {
        $kernel = self::bootKernel();
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $startService = self::getContainer()->get(StartMeetingService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'Room Tomorrow'));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local2.de'));
        $permission = new RoomsUser();
        $permission->setRoom($room);
        $permission->setUser($user);
        $permission->setLobbyModerator(true);
        $manager->persist($permission);
        $manager->flush();
        self::assertEquals('/room/dashboard?color=danger&snack=Der%20Beitritt%20ist%20nur%20von%20'.$room->getStart()->format('d.m.Y').'%20'.$room->getStart()->format('H:i').'%20bis%20'.$room->getEnddate()->format('d.m.Y').'%20'.$room->getEnddate()->format('H:i').'%20m%C3%B6glich.', $startService->startMeeting($room,$user,'b'));
        //$routerService = static::getContainer()->get('router');
        //$myCustomService = static::getContainer()->get(CustomService::class);
    }
    public function testNoRoom(): void
    {
        $kernel = self::bootKernel();
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $startService = self::getContainer()->get(StartMeetingService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'Room Tomorrow'));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local2.de'));
        $permission = new RoomsUser();
        $permission->setRoom($room);
        $permission->setUser($user);
        $permission->setLobbyModerator(true);
        $manager->persist($permission);
        $manager->flush();
        self::assertEquals('/room/dashboard?color=danger&snack=Die%20Konferenz%20wurde%20nicht%20gefunden.%20Bitte%20geben%20Sie%20Ihre%20Zugangsdaten%20erneut%20ein.', $startService->startMeeting(null,$user,'b'));
        //$routerService = static::getContainer()->get('router');
        //$myCustomService = static::getContainer()->get(CustomService::class);
    }
}
