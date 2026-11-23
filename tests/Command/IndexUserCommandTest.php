<?php

namespace App\Tests\Command;

use App\Command\IndexUserCommand;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class IndexUserCommandTest extends KernelTestCase
{
    public function testReindexesUsersAndGroups(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user->setIndexer('stale-index-value');
        $em->flush();

        $command = self::getContainer()->get(IndexUserCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        $display = $tester->getDisplay();
        self::assertStringContainsString('[OK] we reindex 9 users', $display);
        self::assertStringContainsString('[OK] we reindex 1 Groups', $display);
        self::assertStringContainsString(' 9/9', $display);
        self::assertStringContainsString(' 1/1', $display);

        $em->clear();
        $reloaded = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        self::assertNotSame('stale-index-value', $reloaded->getIndexer());
        self::assertStringContainsString('test@local.de', $reloaded->getIndexer());
    }
}
