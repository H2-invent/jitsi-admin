<?php

namespace App\Tests;

use App\Entity\RoomsUser;
use App\Repository\LobbyWaitungUserRepository;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\RoomService;
use App\Service\StartMeetingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StartServiceTest extends KernelTestCase
{
    public function testUserIsOrganizer(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $startService = self::getContainer()->get(StartMeetingService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 1'));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $paramterBag = self::getContainer()->get(ParameterBagInterface::class);
        self::assertEquals(new RedirectResponse(
            'jitsi-meet://meet.jit.si2/123456781?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzNDU2NzgxIiwiY29udGV4dCI6eyJ1c2VyIjp7Im5hbWUiOiJVc2VyLCBUZXN0LCB0ZXN0QGxvY2FsLmRlIn19LCJtb2RlcmF0b3IiOnRydWV9.rgoK2HJlevbuRz1M3cIrkmJSARhQ6addjyaBG6zP4qU#config.subject=%22TestMeeting: 1%22'),
            $startService->startMeeting($room, $user, 'a', $user->getFormatedName($paramterBag->get('laf_showNameInConference'))));
        self::assertStringContainsString(
            'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzNDU2NzgxIiwiY29udGV4dCI6eyJ1c2VyIjp7Im5hbWUiOiJVc2VyLCBUZXN0LCB0ZXN0QGxvY2FsLmRlIn19LCJtb2RlcmF0b3IiOnRydWV9.rgoK2HJlevbuRz1M3cIrkmJSARhQ6addjyaBG6zP4qU',
            $startService->startMeeting($room, $user, 'b', $user->getFormatedName($paramterBag->get('laf_showNameInConference'))));

    }

    public function test_UserIsOrganizer_FixedRoom(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $startService = self::getContainer()->get(StartMeetingService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'This Room has no participants and fixed room'));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $paramterBag = self::getContainer()->get(ParameterBagInterface::class);
        self::assertEquals(new RedirectResponse(
            'jitsi-meet://meet.jit.si2/561d6f51s6f?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiNTYxZDZmNTFzNmYiLCJjb250ZXh0Ijp7InVzZXIiOnsibmFtZSI6IlVzZXIsIFRlc3QsIHRlc3RAbG9jYWwuZGUifX0sIm1vZGVyYXRvciI6dHJ1ZX0.QGhyBYZF_hkMZu1tRQF7mfGv1aLV9Ewp21vgd4cGDto#config.subject=%22This Room has no participants and fixed room%22'),
            $startService->startMeeting($room, $user, 'a', $user->getFormatedName($paramterBag->get('laf_showNameInConference'))));
        self::assertStringContainsString(
            "jwt: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiNTYxZDZmNTFzNmYiLCJjb250ZXh0Ijp7InVzZXIiOnsibmFtZSI6IlVzZXIsIFRlc3QsIHRlc3RAbG9jYWwuZGUifX0sIm1vZGVyYXRvciI6dHJ1ZX0.QGhyBYZF_hkMZu1tRQF7mfGv1aLV9Ewp21vgd4cGDto'",
            $startService->startMeeting($room, $user, 'b', $user->getFormatedName($paramterBag->get('laf_showNameInConference'))));
        self::assertStringContainsString(
            "<title>This Room has no participants and fixed room</title>",
            $startService->startMeeting($room, $user, 'b', $user->getFormatedName($paramterBag->get('laf_showNameInConference'))));
        self::assertStringContainsString(
            "<title>This Room has no participants and fixed room</title>",
            $startService->startMeeting($room, $user, 'b', $user->getFormatedName($paramterBag->get('laf_showNameInConference'))));
    }

    public function test_RoomHasLobby_userisOrganizer(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $startService = self::getContainer()->get(StartMeetingService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'This is a room with Lobby'));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $paramterBag = self::getContainer()->get(ParameterBagInterface::class);
        $this->assertStringContainsString('startIframe',
            $startService->startMeeting($room, $user, 'b', $user->getFormatedName($paramterBag->get('laf_showNameInConference'))));
        $this->assertStringContainsString('/room/lobby/start/moderator/a/' . $room->getUidReal(),
            $startService->startMeeting($room, $user, 'a', $user->getFormatedName($paramterBag->get('laf_showNameInConference'))));
//        self::assertEquals(new RedirectResponse('/room/lobby/moderator/b/561ghj984ssdfdf'), $startService->startMeeting($room, $user, 'a',$user->getFormatedName($paramterBag->get('laf_showNameInConference'))));

    }

    public function test_RoomHasLobby_userisnoLobbyMOderator(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $startService = self::getContainer()->get(StartMeetingService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'This is a room with Lobby'));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local2.de'));
        $paramterBag = self::getContainer()->get(ParameterBagInterface::class);
        $lobbyRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $lobbyUser = $lobbyRepo->findOneBy(array('user' => $user, 'room' => $room));
        self::assertNull($lobbyUser);
        self::assertStringContainsString('https://'.$room->getServer()->getUrl().'/external_api.js',
            $startService->startMeeting($room, $user, 'a', $user->getFormatedName($paramterBag->get('laf_showNameInConference'))));
        self::assertStringContainsString("var type = 'a'",
            $startService->startMeeting($room, $user, 'a', $user->getFormatedName($paramterBag->get('laf_showNameInConference'))));
        self::assertStringContainsString("var type = 'a'",
            $startService->startMeeting($room, $user, 'a', $user->getFormatedName($paramterBag->get('laf_showNameInConference'))));
        self::assertStringContainsString('topic=http%3A%2F%2Flocalhost%2Flobby%2Fparticipants%2Fa%2F'.$room->getUidReal().'%2F'.$user->getUid(),
            $startService->startMeeting($room, $user, 'a', $user->getFormatedName($paramterBag->get('laf_showNameInConference'))));
        self::assertStringContainsString('topic=http%3A%2F%2Flocalhost%2Flobby%2Fbroadcast%2F'.$room->getUidReal(),
            $startService->startMeeting($room, $user, 'a', $user->getFormatedName($paramterBag->get('laf_showNameInConference'))));

        self::assertStringContainsString('topic=http%3A%2F%2Flocalhost%2Flobby%2Fbroadcast%2F'.$room->getUidReal(),
            $startService->startMeeting($room, $user, 'b', $user->getFormatedName($paramterBag->get('laf_showNameInConference'))));
        $lobbyUser = $lobbyRepo->findOneBy(array('user' => $user, 'room' => $room));
        self::assertNotNull($lobbyUser);
        self::assertEquals('b',$lobbyUser->getType());

    }

    public function testRoomHasLobby_userisLobbyModerator(): void
    {
        $kernel = self::bootKernel();
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $startService = self::getContainer()->get(StartMeetingService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'This is a room with Lobby'));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local2.de'));
        $permission = new RoomsUser();
        $permission->setRoom($room);
        $permission->setUser($user);
        $permission->setLobbyModerator(true);
        $manager->persist($permission);
        $manager->flush();
        $roomService = self::getContainer()->get(RoomService::class);
        $paramterBag = self::getContainer()->get(ParameterBagInterface::class);
        self::assertStringContainsString(
            "displayName: '".$user->getFormatedName($paramterBag->get('laf_showNameInConference')."'"),
            $startService->startMeeting($room, $user, 'a', $user->getFormatedName($paramterBag->get('laf_showNameInConference'))));
        self::assertStringContainsString(
            $roomService->generateJwt($room,$user, $user->getFormatedName($paramterBag->get('laf_showNameInConference'))),
            $startService->startMeeting($room, $user, 'b', $user->getFormatedName($paramterBag->get('laf_showNameInConference'))));


    }

    public function testRoomisToEarly_User_isLogin(): void
    {
        $kernel = self::bootKernel();
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $startService = self::getContainer()->get(StartMeetingService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'Room Tomorrow'));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local2.de'));
        $permission = new RoomsUser();
        $permission->setRoom($room);
        $permission->setUser($user);
        $permission->setLobbyModerator(true);
        $manager->persist($permission);
        $manager->flush();
        $paramterBag = self::getContainer()->get(ParameterBagInterface::class);
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        $res = $urlGen->generate('dashboard',array('color'=>'danger','snack'=>'Der Beitritt ist nur von ' . (clone $room->getStart())->modify('-30min')->format('d.m.Y H:i')  . ' bis ' . $room->getEnddate()->format('d.m.Y') . ' ' . $room->getEnddate()->format('H:i') . ' möglich.'));
        self::assertEquals(new RedirectResponse($res), $startService->startMeeting($room, $user, 'b', $user->getFormatedName($paramterBag->get('laf_showNameInConference'))));

    }

    public function testNoRoom(): void
    {
        $kernel = self::bootKernel();
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $startService = self::getContainer()->get(StartMeetingService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'Room Tomorroww'));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local2.de'));
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        $paramterBag = self::getContainer()->get(ParameterBagInterface::class);
        $res = $urlGen->generate('dashboard',array('color'=>'danger','snack'=>'Die Konferenz wurde nicht gefunden. Bitte geben Sie Ihre Zugangsdaten erneut ein.'));
        self::assertEquals(
            new RedirectResponse($res),
            $startService->startMeeting(null, $user, 'b', $startService->startMeeting($room, $user, 'b', $user->getFormatedName($paramterBag->get('laf_showNameInConference')))));

    }
}
