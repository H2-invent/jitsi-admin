<?php

namespace App\Tests\Tag;

use App\Command\Tag\TagPriorityCommand;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class TagPriorityCommandTest extends KernelTestCase
{
    public function testSetPriority(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $tag = $tagRepo->findOneBy(['title' => 'Test Tag 0']);
        self::assertNotNull($tag);
        self::assertSame(0, $tag->getPriority());

        $command = self::getContainer()->get(TagPriorityCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['tagId' => $tag->getId(), 'prio' => 42]);
        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('Priority of Test Tag 0 is now set to 42', $tester->getDisplay());

        $em->refresh($tag);
        self::assertSame(42, $tag->getPriority());
    }

    public function testUnknownTagFails(): void
    {
        self::bootKernel();
        $command = self::getContainer()->get(TagPriorityCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['tagId' => -1, 'prio' => 5]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Tag does not exist', $tester->getDisplay());
    }
}
