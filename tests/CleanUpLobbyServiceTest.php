<?php

namespace App\Tests;

use App\Repository\LobbyWaitungUserRepository;
use App\Service\CleanupLobbyService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CleanUpLobbyServiceTest extends KernelTestCase
{
    public function test8h(): void
    {
        $kernel = self::bootKernel();
        $lobbyRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $lobbyRepoUser = $lobbyRepo->findAll();
        $this->assertSame('test', $kernel->getEnvironment());
        $cleanUp = self::getContainer()->get(CleanupLobbyService::class);
        self::assertEquals(10,sizeof($lobbyRepoUser));
        $res = $cleanUp->cleanUp(8);
        self::assertEquals(3,sizeof($res));
        $lobbyRepoUser = $lobbyRepo->findAll();
        self::assertEquals(7,sizeof($lobbyRepoUser));
    }

    public function test4h(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $cleanUp = self::getContainer()->get(CleanupLobbyService::class);
        $lobbyRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $lobbyRepoUser = $lobbyRepo->findAll();
        self::assertEquals(10,sizeof($lobbyRepoUser));
        $res = $cleanUp->cleanUp(4);
        self::assertEquals(7,sizeof($res));
        $lobbyRepoUser = $lobbyRepo->findAll();
        self::assertEquals(3,sizeof($lobbyRepoUser));

    }
    public function test4hwithCommand(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $command = $application->find('app:lobby:cleanUp');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('arg1'=>4));
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(' [NOTE] We delete all Lobbyusers which are older then 4 hours', $output);
        $this->assertStringContainsString(' [OK] We deleted 7 lobby users', $output);
    }
    public function testhwithCommand(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $command = $application->find('app:lobby:cleanUp');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(' [NOTE] We delete all Lobbyusers which are older then 72 hours', $output);
        $this->assertStringContainsString(' [OK] We deleted 0 lobby users', $output);
    }
}
