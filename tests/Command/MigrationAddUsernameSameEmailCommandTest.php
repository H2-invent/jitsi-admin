<?php

namespace App\Tests\Command;

use App\Command\MigrationAddUsernameSameEmailCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MigrationAddUsernameSameEmailCommandTest extends KernelTestCase
{
    public function testUsernameIsFilledFromEmailWhenEmpty(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);

        $user = new User();
        $user->setEmail('migration-username-same-email@test.local');
        $user->setUuid(md5(uniqid('', true)));
        $user->setPassword('test');
        $user->setCreatedAt(new \DateTime());
        $em->persist($user);
        $em->flush();
        $id = $user->getId();

        self::assertNull($user->getUsername());

        $commandTester = new CommandTester(self::getContainer()->get(MigrationAddUsernameSameEmailCommand::class));
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('You have a new command!', $commandTester->getDisplay());

        $em->clear();
        $migrated = $userRepo->find($id);
        self::assertNotNull($migrated);
        self::assertSame('migration-username-same-email@test.local', $migrated->getUsername());
    }
}
