<?php

namespace App\Tests\Command;

use App\Command\AddSlugToServerCommand;
use App\Entity\Server;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class AddSlugToServerCommandTest extends KernelTestCase
{
    private function createServerWithoutSlug(): Server
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $server = new Server();
        $server->setUrl('no-slug-server.de');
        $server->setServerName('Server without slug');
        $server->setAppId('appId');
        $server->setAppSecret('appSecret');
        $server->setSlug('');
        $server->setJwtModeratorPosition(1);
        $server->setAdministrator($user);
        $server->addUser($user);
        $em->persist($server);
        $em->flush();

        return $server;
    }

    public function testAddsSlugToServerWithoutSlug(): void
    {
        self::bootKernel();
        $server = $this->createServerWithoutSlug();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $command = self::getContainer()->get(AddSlugToServerCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('noslugserverde', $tester->getDisplay());
        self::assertStringContainsString('[OK] We transformed 1 Servers', $tester->getDisplay());

        $em->clear();
        $reloaded = self::getContainer()->get(ServerRepository::class)->find($server->getId());
        self::assertSame('noslugserverde', $reloaded->getSlug());
    }

    public function testReportsZeroWhenEveryServerHasASlug(): void
    {
        self::bootKernel();

        $command = self::getContainer()->get(AddSlugToServerCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('[OK] We transformed 0 Servers', $tester->getDisplay());
    }
}
