<?php

namespace App\Tests\LobbyMessage;

use App\Command\LobbyMessage\LobbyMessageDeleteCommand;
use App\Repository\PredefinedLobbyMessagesRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class LobbyMessageDeleteCommandTest extends KernelTestCase
{
    public function testDeleteConfirmed(): void
    {
        self::bootKernel();
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $message = $messageRepo->findOneBy(['text' => 'Bitte warten/Disabled!']);
        self::assertNotNull($message);
        $messageId = $message->getId();
        $countBefore = count($messageRepo->findAll());

        $command = self::getContainer()->get(LobbyMessageDeleteCommand::class);
        $tester = new CommandTester($command);
        $tester->setInputs(['yes']);
        $tester->execute(['id' => $messageId]);
        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('We delete the message: Bitte warten/Disabled!', $tester->getDisplay());

        self::assertNull($messageRepo->find($messageId));
        self::assertCount($countBefore - 1, $messageRepo->findAll());
    }

    public function testDeleteDeclined(): void
    {
        self::bootKernel();
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $message = $messageRepo->findOneBy(['text' => 'Bitte warten!']);
        self::assertNotNull($message);
        $countBefore = count($messageRepo->findAll());

        $command = self::getContainer()->get(LobbyMessageDeleteCommand::class);
        $tester = new CommandTester($command);
        $tester->setInputs(['no']);
        $tester->execute(['id' => $message->getId()]);
        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('We haven`t deleted the message: Bitte warten!', $tester->getDisplay());

        self::assertNotNull($messageRepo->find($message->getId()));
        self::assertCount($countBefore, $messageRepo->findAll());
    }

    public function testUnknownIdFails(): void
    {
        self::bootKernel();
        $command = self::getContainer()->get(LobbyMessageDeleteCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['id' => 999999]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Wrong ID. no message found', $tester->getDisplay());
    }

    public function testMissingIdFails(): void
    {
        self::bootKernel();
        $command = self::getContainer()->get(LobbyMessageDeleteCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Please enter a valid id', $tester->getDisplay());
    }
}
