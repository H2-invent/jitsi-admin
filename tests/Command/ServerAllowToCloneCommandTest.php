<?php

namespace App\Tests\Command;

use App\Command\ServerAllowToCloneCommand;
use App\Repository\ServerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ServerAllowToCloneCommandTest extends KernelTestCase
{
    public function testTogglesAllowedToClone(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $serverRepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverRepo->findOneBy(['url' => 'meet.jit.si']);
        $serverId = $server->getId();
        self::assertNotTrue($server->isAllowedToCloneForAutoscale());

        $command = self::getContainer()->get(ServerAllowToCloneCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['server' => $serverId]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Server ALLOW to autoscale via api', $tester->getDisplay());

        $em->clear();
        self::assertTrue($serverRepo->find($serverId)->isAllowedToCloneForAutoscale());

        $tester = new CommandTester(self::getContainer()->get(ServerAllowToCloneCommand::class));
        $tester->execute(['server' => $serverId]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Server DISALLOW to autoscale via api', $tester->getDisplay());

        $em->clear();
        self::assertFalse($serverRepo->find($serverId)->isAllowedToCloneForAutoscale());
    }
}
