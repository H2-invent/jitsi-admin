<?php

namespace App\Tests\Tag;

use App\Command\Tag\TagAddToAllCommand;
use App\Repository\RoomsRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class TagAddToAllCommandTest extends KernelTestCase
{
    public function testAddTagToAllRoomsWithoutTag(): void
    {
        self::bootKernel();
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $roomsRepo = self::getContainer()->get(RoomsRepository::class);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tag = $tagRepo->findOneBy(['title' => 'Test Tag Enabled']);
        self::assertNotNull($tag);

        $roomsWithoutTag = $roomsRepo->findRoomsWithNoTags();
        self::assertGreaterThan(0, count($roomsWithoutTag));

        $command = self::getContainer()->get(TagAddToAllCommand::class);
        $tester = new CommandTester($command);
        $tester->setInputs([(string)$tag->getId()]);
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        $output = $tester->getDisplay();
        self::assertStringContainsString('0/' . count($roomsWithoutTag), $output);
        self::assertStringContainsString(count($roomsWithoutTag) . '/' . count($roomsWithoutTag), $output);
        self::assertStringContainsString('This are all your tags', $output);

        self::assertCount(0, $roomsRepo->findRoomsWithNoTags());
        foreach ($roomsWithoutTag as $room) {
            $em->refresh($room);
            self::assertNotNull($room->getTag());
            self::assertSame($tag->getId(), $room->getTag()->getId());
        }
    }

    public function testUnknownTagIdFails(): void
    {
        self::bootKernel();
        $command = self::getContainer()->get(TagAddToAllCommand::class);
        $tester = new CommandTester($command);
        $tester->setInputs(['999999']);
        $tester->execute([]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('No Tag found', $tester->getDisplay());
    }
}
