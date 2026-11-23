<?php

namespace App\Tests\Tag;

use App\Command\Tag\TagDisableCommand;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class TagDisableCommandTest extends KernelTestCase
{
    public function testDisableEnabledTag(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $tag = $tagRepo->findOneBy(['title' => 'Test Tag Enabled']);
        self::assertNotNull($tag);
        self::assertFalse($tag->getDisabled());

        $command = self::getContainer()->get(TagDisableCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['tagId' => $tag->getId()]);
        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('Tag Test Tag Enabled is DISABLED', $tester->getDisplay());

        $em->refresh($tag);
        self::assertTrue($tag->getDisabled());
    }

    public function testDisableUnknownTagFails(): void
    {
        self::bootKernel();
        $command = self::getContainer()->get(TagDisableCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['tagId' => -1]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Tag does not exist', $tester->getDisplay());
    }
}
