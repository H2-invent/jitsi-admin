<?php

namespace App\Tests\LobbyMessage;

use App\Command\LobbyMessage\LobbyMessageCreateCommand;
use App\Repository\PredefinedLobbyMessagesRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class LobbyMessageCreateCommandTest extends KernelTestCase
{
    public function testCreateWithArguments(): void
    {
        self::bootKernel();
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $countBefore = count($messageRepo->findAll());

        $command = self::getContainer()->get(LobbyMessageCreateCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['text' => 'Dedicated Test Message', 'prio' => 5]);
        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('We create a new Predefined message.', $tester->getDisplay());

        $message = $messageRepo->findOneBy(['text' => 'Dedicated Test Message']);
        self::assertNotNull($message);
        self::assertSame(5, $message->getPriority());
        self::assertTrue($message->isActive());
        self::assertNotNull($message->getCreatedAt());
        self::assertCount($countBefore + 1, $messageRepo->findAll());
    }

    public function testCreateInteractively(): void
    {
        self::bootKernel();
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $countBefore = count($messageRepo->findAll());

        $command = self::getContainer()->get(LobbyMessageCreateCommand::class);
        $tester = new CommandTester($command);
        $tester->setInputs(['Interactive Message', 10, 'no']);
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        $message = $messageRepo->findOneBy(['text' => 'Interactive Message']);
        self::assertNotNull($message);
        self::assertSame(10, $message->getPriority());
        self::assertFalse($message->isActive());
        self::assertCount($countBefore + 1, $messageRepo->findAll());
    }

    public function testDuplicateTextFails(): void
    {
        self::bootKernel();
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $countBefore = count($messageRepo->findAll());

        $command = self::getContainer()->get(LobbyMessageCreateCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['text' => 'Bitte warten!']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('The message is already defined', $tester->getDisplay());
        self::assertCount($countBefore, $messageRepo->findAll());
    }
}
