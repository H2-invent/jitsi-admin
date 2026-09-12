<?php

namespace App\Tests\Command;

use App\Command\CleanUpLobbyCommand;
use App\Repository\LobbyWaitungUserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CleanUpLobbyCommandTest extends KernelTestCase
{
    public function testDefaultMaxAgeKeepsRecentLobbyUsers(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $before = sizeof($repository->findAll());

        $command = self::getContainer()->get(CleanUpLobbyCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('We delete all Lobbyusers which are older then 72 hours', $tester->getDisplay());
        $this->assertStringContainsString('We deleted 0 lobby users', $tester->getDisplay());
        $this->assertSame($before, sizeof($repository->findAll()));
    }

    public function testMaxAgeZeroDeletesLobbyUsers(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $before = sizeof($repository->findAll());
        $this->assertGreaterThan(0, $before);

        $command = self::getContainer()->get(CleanUpLobbyCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['maxAge' => 0]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('We delete all Lobbyusers which are older then 0 hours', $tester->getDisplay());
        $this->assertStringContainsString(sprintf('We deleted %d lobby users', $before), $tester->getDisplay());
        $this->assertSame(0, sizeof($repository->findAll()));
    }
}
