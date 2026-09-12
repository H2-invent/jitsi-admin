<?php

namespace App\Tests\Tag;

use App\Command\Tag\TagEnableCommand;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class TagEnableCommandTest extends KernelTestCase
{
    public function testEnableDisabledTag(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $tag = $tagRepo->findOneBy(['title' => 'Test Tag Disabled']);
        self::assertNotNull($tag);
        self::assertTrue($tag->getDisabled());

        $command = self::getContainer()->get(TagEnableCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['tagId' => $tag->getId()]);
        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('Tag Test Tag Disabled is ENABLED', $tester->getDisplay());

        $em->refresh($tag);
        self::assertFalse($tag->getDisabled());
    }

    public function testEnableUnknownTagFails(): void
    {
        self::bootKernel();
        $command = self::getContainer()->get(TagEnableCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['tagId' => -1]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Tag does not exist', $tester->getDisplay());
    }
}
