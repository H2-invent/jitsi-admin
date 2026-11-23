<?php

namespace App\Tests\Command;

use App\Command\EmailTestCommand;
use App\Repository\ServerRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class EmailTestCommandTest extends KernelTestCase
{
    public function testFailsWithUnknownServerId(): void
    {
        self::bootKernel();

        $command = self::getContainer()->get(EmailTestCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['serverId' => 999999, 'email' => 'recipient@local.de']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Enter a valid Server ID', $tester->getDisplay());
    }

    public function testFailsWithoutEmail(): void
    {
        self::bootKernel();
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);

        $command = self::getContainer()->get(EmailTestCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['serverId' => $server->getId()]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Enter an email', $tester->getDisplay());
    }

    public function testSendsTestEmail(): void
    {
        self::bootKernel();
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);

        $command = self::getContainer()->get(EmailTestCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['serverId' => $server->getId(), 'email' => 'recipient@local.de']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
    }
}
