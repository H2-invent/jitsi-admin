<?php

namespace App\Tests\Tag;

use App\Command\Tag\TagListCommand;
use App\Repository\TagRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class TagListCommandTest extends KernelTestCase
{
    public function testListTags(): void
    {
        self::bootKernel();
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $enabled = $tagRepo->findOneBy(['title' => 'Test Tag Enabled']);
        $disabled = $tagRepo->findOneBy(['title' => 'Test Tag Disabled']);
        self::assertNotNull($enabled);
        self::assertNotNull($disabled);

        $command = self::getContainer()->get(TagListCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        $output = $tester->getDisplay();
        self::assertStringContainsString('[X] Test Tag Enabled', $output);
        self::assertStringContainsString('[ ] Test Tag Disabled', $output);
        self::assertStringContainsString('This are all your tags', $output);
        self::assertStringContainsString('Prio: -10', $output);
    }
}
