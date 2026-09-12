<?php

namespace App\Tests\LobbyMessage;

use App\Command\LobbyMessage\LobbyMessageListCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LobbyMessageListCommandTest extends KernelTestCase
{
    public function testListMessages(): void
    {
        self::bootKernel();
        $command = self::getContainer()->get(LobbyMessageListCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        $output = $tester->getDisplay();
        self::assertStringContainsString('Active', $output);
        self::assertStringContainsString('Text', $output);
        self::assertStringContainsString('Prio', $output);
        self::assertStringContainsString('Bitte warten!', $output);
        self::assertStringContainsString('Bitte warten/Disabled!', $output);
        self::assertStringContainsString('Wir haben andere Themen!', $output);
        self::assertStringContainsString('[X]', $output);
        self::assertStringContainsString('[ ]', $output);
        self::assertStringContainsString('This are all you messages.', $output);
    }
}
