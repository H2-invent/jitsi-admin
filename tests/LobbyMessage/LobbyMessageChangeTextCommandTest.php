<?php

namespace App\Tests\LobbyMessage;

use App\Command\LobbyMessage\LobbyMessageChangeTextCommand;
use App\Repository\PredefinedLobbyMessagesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class LobbyMessageChangeTextCommandTest extends KernelTestCase
{
    public function testChangeText(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $message = $messageRepo->findOneBy(['text' => 'Bitte warten!']);
        self::assertNotNull($message);
        $messageId = $message->getId();

        $command = self::getContainer()->get(LobbyMessageChangeTextCommand::class);
        $tester = new CommandTester($command);
        $tester->setInputs(['Changed text']);
        $tester->execute(['id' => $messageId]);
        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('You have changed the message to Changed text', $tester->getDisplay());

        $em->refresh($message);
        self::assertSame('Changed text', $message->getText());
        self::assertTrue($message->isActive());
    }

    public function testUnknownIdFails(): void
    {
        self::bootKernel();
        $command = self::getContainer()->get(LobbyMessageChangeTextCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['id' => 999999]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Wrong ID. no message found', $tester->getDisplay());
    }

    public function testMissingIdFails(): void
    {
        self::bootKernel();
        $command = self::getContainer()->get(LobbyMessageChangeTextCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Please enter a valid id', $tester->getDisplay());
    }
}
