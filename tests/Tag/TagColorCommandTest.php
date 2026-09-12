<?php

namespace App\Tests\Tag;

use App\Command\Tag\TagColorCommand;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class TagColorCommandTest extends KernelTestCase
{
    public function testChangeColors(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $tag = $tagRepo->findOneBy(['title' => 'Test Tag Enabled']);
        self::assertNotNull($tag);

        $command = self::getContainer()->get(TagColorCommand::class);
        $tester = new CommandTester($command);
        $tester->setInputs(['#123456', '#abcdef']);
        $tester->execute(['tagId' => $tag->getId()]);
        $tester->assertCommandIsSuccessful();

        $output = preg_replace('/\s+/', ' ', $tester->getDisplay());
        self::assertStringContainsString('Font color of Test Tag Enabled is now set to #123456 and backgroundcolor is set to #abcdef', $output);

        $em->refresh($tag);
        self::assertSame('#123456', $tag->getColor());
        self::assertSame('#abcdef', $tag->getBackgroundColor());
    }

    public function testMissingTagFails(): void
    {
        self::bootKernel();
        $command = self::getContainer()->get(TagColorCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['tagId' => -1]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Tag does not exist', $tester->getDisplay());
    }

    public function testNoTagIdFails(): void
    {
        self::bootKernel();
        $command = self::getContainer()->get(TagColorCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Tag does not exist', $tester->getDisplay());
    }
}
