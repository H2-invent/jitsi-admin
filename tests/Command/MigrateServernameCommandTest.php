<?php

namespace App\Tests\Command;

use App\Command\MigrateServernameCommand;
use App\Entity\Server;
use App\Repository\ServerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateServernameCommandTest extends KernelTestCase
{
    public function testMigrationUsesUrlAsServerName(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $serverRepo = self::getContainer()->get(ServerRepository::class);

        $server = new Server();
        $server->setUrl('migrate-servername.test');
        $server->setServerName('');
        $server->setSlug('migrate-servername');
        $server->setJwtModeratorPosition(0);
        $em->persist($server);
        $em->flush();
        $id = $server->getId();

        $commandTester = new CommandTester(self::getContainer()->get(MigrateServernameCommand::class));
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $display = $commandTester->getDisplay();
        $this->assertStringContainsString('[INFO] We rename the server with the url migrate-servername.test', $display);
        $this->assertStringContainsString('[OK] We rename # 1 of servers', $display);

        $em->clear();
        $renamed = $serverRepo->find($id);
        self::assertNotNull($renamed);
        self::assertSame('migrate-servername.test', $renamed->getServerName());
    }
}
