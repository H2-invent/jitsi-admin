<?php

namespace App\Tests;

use App\Entity\LobbyWaitungUser;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\Lobby\DirectSendService;
use App\Service\Lobby\ToParticipantWebsocketService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;

class LobbyToParticipantsTest extends KernelTestCase
{
    public function testInAppRedirect(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $directSend = $this->getContainer()->get(DirectSendService::class);
        $hub = new MockHub('http://localhost:3000/.well-known/mercure', new StaticTokenProvider('test'), function (Update $update): string {
            if(strpos($update->getData(),'snackbar')>0){
                self::assertEquals('{"type":"snackbar","message":"Sie wurden zu der Konferenz zugelassen und werden in einigen Sekunden weitergeleitet.","color":"success"}', $update->getData());
            }
            if(strpos($update->getData(),'jitsi-meet')>0){
                self::assertEquals('{"type":"redirect","url":"jitsi-meet:\/\/meet.jit.si2\/12313231ghjgfdsdf?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzMTMyMzFnaGpnZmRzZGYiLCJjb250ZXh0Ijp7InVzZXIiOnsibmFtZSI6IlRlc3QgVXNlciJ9fSwibW9kZXJhdG9yIjpmYWxzZX0.9ND7c-K_wWEciD3NQZiDX-Bhn4jY_XDnqiZXquRpHD4#config.subject=%22This is a room with Lobby%22","timeout":5000}', $update->getData());
            }
            return 'id';
        });
        $directSend->setMercurePublisher($hub);
        $lobbyToParticipant = self::getContainer()->get(ToParticipantWebsocketService::class);
        $lobbyToParticipant->setDirectSend($directSend);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'This is a room with Lobby'));
        $user2 = $userRepo->findOneBy(array('email' => 'test@local2.de'));
        $lobbyUser = new LobbyWaitungUser();
        $lobbyUser->setType('a');
        $lobbyUser->setUser($user2);
        $lobbyUser->setRoom($room);
        $lobbyUser->setUid('lkjhdslkfjhdskjhfkds');
        $lobbyUser->setCreatedAt(new \DateTime());
        $lobbyUser->setShowName($user2->getFirstName().' '.$user2->getLastName());
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->persist($lobbyUser);
        $manager->flush();
        $lobbyToParticipant->acceptLobbyUser($lobbyUser);
    }
    public function testinBrowser(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'This is a room with Lobby'));
        $user2 = $userRepo->findOneBy(array('email' => 'test@local2.de'));
        $lobbyUser = new LobbyWaitungUser();
        $lobbyUser->setType('b');
        $lobbyUser->setUser($user2);
        $lobbyUser->setRoom($room);
        $lobbyUser->setUid('lkjhdslkfjhdskjhfkds');
        $lobbyUser->setCreatedAt(new \DateTime());
        $lobbyUser->setShowName($user2->getFirstName().' '.$user2->getLastName());
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->persist($lobbyUser);
        $manager->flush();

        $directSend = $this->getContainer()->get(DirectSendService::class);

        $hub = new MockHub('http://localhost:3000/.well-known/mercure', new StaticTokenProvider('test'), function (Update $update): string {
            if(strpos($update->getData(),'newJitsi')>0){
                self::assertEquals('{"type":"newJitsi","options":{"options":{"jwt":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzMTMyMzFnaGpnZmRzZGYiLCJjb250ZXh0Ijp7InVzZXIiOnsibmFtZSI6IlRlc3QgVXNlciJ9fSwibW9kZXJhdG9yIjpmYWxzZX0.9ND7c-K_wWEciD3NQZiDX-Bhn4jY_XDnqiZXquRpHD4","roomName":"12313231ghjgfdsdf","width":"100%","height":400},"roomName":"This is a room with Lobby","domain":"meet.jit.si2","parentNode":"#jitsiWindow","userInfo":{"displayName":"Test User"}}}', $update->getData());
            }
            if(strpos($update->getData(),'snackbar')>0){
                self::assertEquals('{"type":"snackbar","message":"Sie wurden zu der Konferenz zugelassen und werden in einigen Sekunden weitergeleitet.","color":"success"}', $update->getData());
            }
            self::assertEquals(['lobby_WaitingUser_websocket/lkjhdslkfjhdskjhfkds'], $update->getTopics());
            return 'id';
        });
        $directSend->setMercurePublisher($hub);
        $lobbyToParticipant = self::getContainer()->get(ToParticipantWebsocketService::class);
        $lobbyToParticipant->setDirectSend($directSend);
        $lobbyToParticipant->acceptLobbyUser($lobbyUser);
    }
}
