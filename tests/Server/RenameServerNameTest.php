<?php

namespace App\Tests\Server;

use App\Entity\Server;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use App\Service\RenameServerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class RenameServerNameTest extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();

        $serverRepo = $this->getContainer()->get(ServerRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $serverrename = $this->getContainer()->get(RenameServerService::class);
        $s = new Server();
        $s->setUrl('testRename.de');
        $s->setServerName('');
        $s->setAppId('test');
        $s->setAppSecret('test');
        $s->setSlug('testRename');
        $s->setJwtModeratorPosition(1);
        $s->setAdministrator($user);
        $s->addUser($user);
        $server = [];
        $server[] = $s;
        $serverrename->renameServer($server);
        $serverTmp = $serverRepo->findOneBy(['url' => 'testRename.de']);
        $this->assertEquals('testRename.de', $serverTmp->getServerName());
        $this->assertEquals(1, sizeof($server));
        $s->setServerName(null);
        $server = [];
        $server[] = $s;
        $serverrename->renameServer($server);
        $serverTmp = $serverRepo->findOneBy(['url' => 'testRename.de']);
        $this->assertEquals('testRename.de', $serverTmp->getServerName());
        $this->assertEquals(1, sizeof($server));
    }
    public function testExecute()
    {
        $kernel = static::createKernel();
        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $serverRepo = $this->getContainer()->get(ServerRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $serverrename = $this->getContainer()->get(RenameServerService::class);
        $s = new Server();
        $s->setUrl('testRename.de');
        $s->setServerName('');
        $s->setAppId('test');
        $s->setAppSecret('test');
        $s->setSlug('testRename');
        $s->setJwtModeratorPosition(1);
        $s->setAdministrator($user);
        $s->addUser($user);
        $em->persist($s);
        $em->flush();
        $application = new Application($kernel);
        $command = $application->find('app:migrate:servername');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(' [INFO] We rename the server with the url testRename.de', $output);
        $this->assertStringContainsString(' [OK] We rename # 1', $output);
    }
}
