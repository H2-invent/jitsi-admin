<?php

namespace App\Tests\Command;

use App\Command\ConnectServerAndGroupsCommand;
use App\Entity\KeycloakGroupsToServers;
use App\Entity\Server;
use App\Repository\KeycloakGroupsToServersRepository;
use App\Repository\ServerRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ConnectServerAndGroupsCommandTest extends KernelTestCase
{
    private function getServer(): Server
    {
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $this->assertNotNull($server);

        return $server;
    }

    public function testConnectServerAndGroup(): void
    {
        self::bootKernel();
        $server = $this->getServer();
        $repository = self::getContainer()->get(KeycloakGroupsToServersRepository::class);
        $before = sizeof($repository->findAll());

        $command = self::getContainer()->get(ConnectServerAndGroupsCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['serverId' => $server->getId(), 'keycloakGroup' => '//all']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('We added the group //all to the server meet.jit.si', $tester->getDisplay());
        $this->assertSame($before + 1, sizeof($repository->findAll()));

        $connection = $repository->findOneBy(['server' => $server, 'keycloakGroup' => '//all']);
        $this->assertInstanceOf(KeycloakGroupsToServers::class, $connection);
        $this->assertSame($server->getId(), $connection->getServer()->getId());
    }

    public function testConnectAlreadyConnectedGroupFails(): void
    {
        self::bootKernel();
        $server = $this->getServer();
        $command = self::getContainer()->get(ConnectServerAndGroupsCommand::class);

        $first = new CommandTester($command);
        $first->execute(['serverId' => $server->getId(), 'keycloakGroup' => '//all']);
        $this->assertSame(0, $first->getStatusCode());

        $second = new CommandTester($command);
        $second->execute(['serverId' => $server->getId(), 'keycloakGroup' => '//all']);

        $this->assertSame(1, $second->getStatusCode());
        $this->assertStringContainsString('This Server is already connected to this group', $second->getDisplay());
    }

    public function testConnectUnknownServerFails(): void
    {
        self::bootKernel();
        $command = self::getContainer()->get(ConnectServerAndGroupsCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['serverId' => 999999, 'keycloakGroup' => '//all']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('This server is not available.', $tester->getDisplay());
    }
}
