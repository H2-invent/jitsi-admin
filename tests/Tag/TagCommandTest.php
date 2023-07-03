<?php

namespace App\Tests\Tag;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class TagCommandTest extends KernelTestCase
{
    public function testCreateTag(): void
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $command = $application->find('app:tag:create');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['title' => 'test-Neu']);
        $output = $commandTester->getDisplay();
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $this->assertStringContainsString(' [OK] The Tag test-Neu was added sucessfully', $output);
    }
    public function testEnableTag(): void
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $tag = $tagRepo->findOneBy(['title' => 'Test Tag Disabled']);

        $command = $application->find('app:tag:enable');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['tagId' => $tag->getId()]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(' [OK] Tag Test Tag Disabled is ENABLED', $output);

        $command = $application->find('app:tag:enable');
        $commandTester->execute(['tagId' => -1]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[ERROR] Tag does not exist ', $output);
    }
    public function testDiableTag(): void
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $tag = $tagRepo->findOneBy(['title' => 'Test Tag Enabled']);

        $command = $application->find('app:tag:disable');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['tagId' => $tag->getId()]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString(' [OK] Tag Test Tag Enabled is DISABLED', $output);

        $command = $application->find('app:tag:disable');
        $commandTester->execute(['tagId' => -1]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[ERROR] Tag does not exist ', $output);
    }
    public function testdeleteTag(): void
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $tag = $tagRepo->findOneBy(['title' => 'Test Tag Enabled']);

        $command = $application->find('app:tag:delete');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['tagId' => $tag->getId()]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString(' [OK] Tag Test Tag Enabled is DELETED ', $output);

        $command = $application->find('app:tag:delete');
        $commandTester->execute(['tagId' => -1]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[ERROR] Tag does not exist ', $output);
    }
    public function testListTag(): void
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $command = $application->find('app:tag:list');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();

        $tagRepo = self::getContainer()->get(TagRepository::class);
        $this->assertStringContainsString('[X] Test Tag Enabled', $output);
        $this->assertStringContainsString('[ ] Test Tag Disabled', $output);
    }

    public function testTagAll(): void
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $command = $application->find('app:tag:addToAll');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();

        $tagRepo = self::getContainer()->get(TagRepository::class);
        $this->assertStringContainsString('0/70', $output);
        $this->assertStringContainsString('70/70', $output);
    }
}
