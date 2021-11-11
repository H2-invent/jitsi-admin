<?php

namespace App\Tests;

use App\Entity\LobbyWaitungUser;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\DirectSendService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;

class LobbyUpdateServiceTest extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $lobbyUpdateService = $this->getContainer()->get(DirectSendService::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local2.de'));
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 0'));
        $loobyUser = new LobbyWaitungUser();
        $loobyUser->setRoom($room);
        $loobyUser->setUser($user);
        $loobyUser->setCreatedAt(new \DateTime());
        $lobbyUpdateService->newParticipantInLobby($loobyUser);
        $hub = new MockHub('http://localhost:3000/.well-known/mercure', new StaticTokenProvider('test'), function(Update $update): string {
             $this->assertFalse($update->isPrivate());
            return 'id';
        });



        //$routerService = self::$container->get('router');
        //$myCustomService = self::$container->get(CustomService::class);
    }
}
