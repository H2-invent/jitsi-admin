<?php

namespace App\Tests\Tag;

use App\Command\Tag\TagDeleteCommand;
use App\Repository\TagRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class TagDeleteCommandTest extends KernelTestCase
{
    public function testDeleteTag(): void
    {
        self::bootKernel();
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $tag = $tagRepo->findOneBy(['title' => 'Test Tag 4']);
        self::assertNotNull($tag);
        $tagId = $tag->getId();

        $command = self::getContainer()->get(TagDeleteCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['tagId' => $tagId]);
        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('Tag Test Tag 4 is DELETED', $tester->getDisplay());

        self::assertNull($tagRepo->find($tagId));
    }

    public function testDeleteUnknownTagFails(): void
    {
        self::bootKernel();
        $command = self::getContainer()->get(TagDeleteCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['tagId' => -1]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Tag does not exist', $tester->getDisplay());
    }
}
