<?php

namespace App\Tests\LobbyMessage;

use App\Command\LobbyMessage\LobbyMessageDeactivateCommand;
use App\Repository\PredefinedLobbyMessagesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class LobbyMessageDeactivateCommandTest extends KernelTestCase
{
    public function testDisableActiveMessage(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $message = $messageRepo->findOneBy(['text' => 'Bitte warten!']);
        self::assertNotNull($message);
        self::assertTrue($message->isActive());

        $command = self::getContainer()->get(LobbyMessageDeactivateCommand::class);
        $tester = new CommandTester($command);
        $tester->setInputs(['yes']);
        $tester->execute(['id' => $message->getId()]);
        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('You have DISABLED the message', $tester->getDisplay());

        $em->refresh($message);
        self::assertFalse($message->isActive());
    }

    public function testEnableInactiveMessage(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $message = $messageRepo->findOneBy(['text' => 'Bitte warten/Disabled!']);
        self::assertNotNull($message);
        self::assertFalse($message->isActive());

        $command = self::getContainer()->get(LobbyMessageDeactivateCommand::class);
        $tester = new CommandTester($command);
        $tester->setInputs(['yes']);
        $tester->execute(['id' => $message->getId()]);
        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('You have ENABLED the message', $tester->getDisplay());

        $em->refresh($message);
        self::assertTrue($message->isActive());
    }

    public function testDeclineKeepsCurrentState(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $message = $messageRepo->findOneBy(['text' => 'Bitte warten!']);
        self::assertNotNull($message);

        $command = self::getContainer()->get(LobbyMessageDeactivateCommand::class);
        $tester = new CommandTester($command);
        $tester->setInputs(['no']);
        $tester->execute(['id' => $message->getId()]);
        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('You have ENABLED the message', $tester->getDisplay());

        $em->refresh($message);
        self::assertTrue($message->isActive());
    }

    public function testUnknownIdFails(): void
    {
        self::bootKernel();
        $command = self::getContainer()->get(LobbyMessageDeactivateCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['id' => 999999]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Wrong ID. no message found', $tester->getDisplay());
    }

    public function testMissingIdFails(): void
    {
        self::bootKernel();
        $command = self::getContainer()->get(LobbyMessageDeactivateCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Please enter a valid id', $tester->getDisplay());
    }
}
