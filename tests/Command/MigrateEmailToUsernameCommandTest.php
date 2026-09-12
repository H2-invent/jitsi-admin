<?php

namespace App\Tests\Command;

use App\Command\MigrateEmailToUsernameCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateEmailToUsernameCommandTest extends KernelTestCase
{
    public function testMigrationSetsUsernameToEmail(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);

        $user = new User();
        $user->setEmail('migrate-email-username@test.local');
        $user->setUsername('some-old-username');
        $user->setUuid(md5(uniqid('', true)));
        $user->setPassword('test');
        $user->setCreatedAt(new \DateTime());
        $em->persist($user);
        $em->flush();
        $id = $user->getId();

        $expectedCount = sizeof($userRepo->findAll());

        $commandTester = new CommandTester(self::getContainer()->get(MigrateEmailToUsernameCommand::class));
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString(sprintf('We transform %d User', $expectedCount), $commandTester->getDisplay());

        $em->clear();
        $migrated = $userRepo->find($id);
        self::assertNotNull($migrated);
        self::assertSame('migrate-email-username@test.local', $migrated->getEmail());
        self::assertSame('migrate-email-username@test.local', $migrated->getUsername());
    }
}
