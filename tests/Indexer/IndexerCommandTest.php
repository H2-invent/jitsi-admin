<?php

namespace App\Tests\Indexer;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class IndexerCommandTest extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $command = $application->find('app:index:user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(' [OK] we reindex 9 users', $output);
        $this->assertStringContainsString(' [OK] we reindex 1 Groups', $output);
        $this->assertStringContainsString(' 1/1', $output);
        $this->assertStringContainsString(' 9/9', $output);
    }
}
