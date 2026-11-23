<?php

namespace App\Tests\Tag;

use App\Command\Tag\TagCreateCommand;
use App\Repository\TagRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class TagCreateCommandTest extends KernelTestCase
{
    public function testCreateWithArguments(): void
    {
        self::bootKernel();
        $tagRepo = self::getContainer()->get(TagRepository::class);

        $command = self::getContainer()->get(TagCreateCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([
            'title' => 'Dedicated Tag',
            'prio' => 7,
            'fontColor' => '#111111',
            'bgcolor' => '#222222',
        ]);
        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('The Tag Dedicated Tag was added sucessfully', $tester->getDisplay());

        $tag = $tagRepo->findOneBy(['title' => 'Dedicated Tag']);
        self::assertNotNull($tag);
        self::assertFalse($tag->getDisabled());
        self::assertSame(7, $tag->getPriority());
        self::assertSame('#111111', $tag->getColor());
        self::assertSame('#222222', $tag->getBackgroundColor());
    }

    public function testCreateInteractively(): void
    {
        self::bootKernel();
        $tagRepo = self::getContainer()->get(TagRepository::class);

        $command = self::getContainer()->get(TagCreateCommand::class);
        $tester = new CommandTester($command);
        $tester->setInputs(['Interactive Tag', 'no', '3', '#333333', '#444444']);
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('The Tag Interactive Tag was added sucessfully', $tester->getDisplay());

        $tag = $tagRepo->findOneBy(['title' => 'Interactive Tag']);
        self::assertNotNull($tag);
        self::assertFalse($tag->getDisabled());
        self::assertSame(3, $tag->getPriority());
        self::assertSame('#333333', $tag->getColor());
        self::assertSame('#444444', $tag->getBackgroundColor());
    }

    public function testCreateDisabledInteractively(): void
    {
        self::bootKernel();
        $tagRepo = self::getContainer()->get(TagRepository::class);

        $command = self::getContainer()->get(TagCreateCommand::class);
        $tester = new CommandTester($command);
        $tester->setInputs(['Disabled Interactive Tag', 'yes', '1', '#555555', '#666666']);
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        $tag = $tagRepo->findOneBy(['title' => 'Disabled Interactive Tag']);
        self::assertNotNull($tag);
        self::assertTrue($tag->getDisabled());
        self::assertSame(1, $tag->getPriority());
    }

    public function testDuplicateTitleFails(): void
    {
        self::bootKernel();
        $command = self::getContainer()->get(TagCreateCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['title' => 'Test Tag Enabled']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('The Tag is already defined', $tester->getDisplay());
    }
}
