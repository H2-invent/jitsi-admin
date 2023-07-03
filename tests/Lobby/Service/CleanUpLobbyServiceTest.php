<?php

namespace App\Tests\Lobby\Service;

use App\Entity\CallerId;
use App\Entity\CallerSession;
use App\Repository\LobbyWaitungUserRepository;
use App\Service\CleanupLobbyService;
use Doctrine\ORM\EntityManagerInterface;
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
        self::assertEquals(10, sizeof($lobbyRepoUser));
        $res = $cleanUp->cleanUp(8);
        self::assertEquals(3, sizeof($res));
        $lobbyRepoUser = $lobbyRepo->findAll();
        self::assertEquals(7, sizeof($lobbyRepoUser));
    }

    public function test4h(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $cleanUp = self::getContainer()->get(CleanupLobbyService::class);
        $lobbyRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $lobbyRepoUser = $lobbyRepo->findAll();
        self::assertEquals(10, sizeof($lobbyRepoUser));
        $res = $cleanUp->cleanUp(4);
        self::assertEquals(7, sizeof($res));
        $lobbyRepoUser = $lobbyRepo->findAll();
        self::assertEquals(3, sizeof($lobbyRepoUser));
    }

    public function test4hwithCallerSession(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $cleanUp = self::getContainer()->get(CleanupLobbyService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $lobbyRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $lobbyRepoUser = $lobbyRepo->findAll();
        foreach ($lobbyRepoUser as $data) {
            $callerID = new CallerId();
            $callerID->setCreatedAt(new \DateTime());
            $callerID->setCallerId('test');
            $callerID->setRoom($data->getRoom());
            $callerID->setUser($data->getUser());
            $session = new CallerSession();
            $session->setAuthOk(false);
            $session->setCallerId('sdffsd');
            $session->setCreatedAt(new \DateTime());
            $session->setShowName('test');
            $session->setSessionId('test');
            $session->setCaller($callerID);
            $data->setCallerSession($session);
            $manager->persist($data);
        }
        $manager->flush();
        $lobbyRepoUser = $lobbyRepo->findAll();
        self::assertEquals(10, sizeof($lobbyRepoUser));
        $res = $cleanUp->cleanUp(4);
        self::assertEquals(7, sizeof($res));
        $lobbyRepoUser = $lobbyRepo->findAll();
        self::assertEquals(3, sizeof($lobbyRepoUser));
    }

    public function test4hwithCommand(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $command = $application->find('app:lobby:cleanUp');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['maxAge' => 4]);
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
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(' [NOTE] We delete all Lobbyusers which are older then 72 hours', $output);
        $this->assertStringContainsString(' [OK] We deleted 0 lobby users', $output);
    }

    public function test0hwithCommand(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $command = $application->find('app:lobby:cleanUp');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['maxAge' => 0]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(' [NOTE] We delete all Lobbyusers which are older then 0 hours', $output);
        $this->assertStringContainsString(' [OK] We deleted 10 lobby users', $output);
    }
}
