<?php

namespace App\Tests\Command;

use App\Command\RemoveServerAndGroupsCommand;
use App\Entity\KeycloakGroupsToServers;
use App\Entity\Server;
use App\Repository\KeycloakGroupsToServersRepository;
use App\Repository\ServerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class RemoveServerAndGroupsCommandTest extends KernelTestCase
{
    private function getServer(): Server
    {
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $this->assertNotNull($server);

        return $server;
    }

    public function testRemoveServerAndGroup(): void
    {
        self::bootKernel();
        $server = $this->getServer();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $connection = (new KeycloakGroupsToServers())
            ->setServer($server)
            ->setKeycloakGroup('//toBeRemoved');
        $em->persist($connection);
        $em->flush();

        $repository = self::getContainer()->get(KeycloakGroupsToServersRepository::class);
        $this->assertNotNull($repository->findOneBy(['server' => $server, 'keycloakGroup' => '//toBeRemoved']));

        $command = self::getContainer()->get(RemoveServerAndGroupsCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['serverId' => $server->getId(), 'keycloakGroup' => '//toBeRemoved']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('We removed the //toBeRemoved group from the server meet.jit.si', $tester->getDisplay());
        $this->assertNull($repository->findOneBy(['server' => $server, 'keycloakGroup' => '//toBeRemoved']));
    }

    public function testRemoveUnknownConnectionFails(): void
    {
        self::bootKernel();
        $server = $this->getServer();
        $command = self::getContainer()->get(RemoveServerAndGroupsCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['serverId' => $server->getId(), 'keycloakGroup' => '//notConnected']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('This Connection is not set', $tester->getDisplay());
    }

    public function testRemoveUnknownServerFails(): void
    {
        self::bootKernel();
        $command = self::getContainer()->get(RemoveServerAndGroupsCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['serverId' => 999999, 'keycloakGroup' => '//doesNotMatter']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('This server is not available.', $tester->getDisplay());
    }
}
