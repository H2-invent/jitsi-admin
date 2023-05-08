<?php

namespace App\Tests\LobbyMessage;

use App\Repository\PredefinedLobbyMessagesRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LobbyMessageCommandTest extends KernelTestCase
{
    public function testCreateLobbyMessage(): void
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $command = $application->find('app:lobby:message:create');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs([10, 'yes']);
        $commandTester->execute(['text' => 'Neue Nachricht']);
        $commandTester->assertCommandIsSuccessful();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $output = $commandTester->getDisplay();
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $this->assertStringContainsString(' [OK] We create a new Predefined message.', $output);
        self::assertEquals(4, sizeof($messageRepo->findAll()));
        self::assertEquals(true, $messageRepo->findAll()[3]->isActive());
        self::assertEquals(10, $messageRepo->findAll()[3]->getPriority());
    }

    public function testCreate2LobbyMessage(): void
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $command = $application->find('app:lobby:message:create');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['neue Nachricht', 10, 'no']);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $output = $commandTester->getDisplay();
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $this->assertStringContainsString(' [OK] We create a new Predefined message.', $output);
        self::assertEquals(4, sizeof($messageRepo->findAll()));
        self::assertEquals(false, $messageRepo->findAll()[3]->isActive());
        self::assertEquals(10, $messageRepo->findAll()[3]->getPriority());
    }


    public function testChangeLobbyMessage(): void
    {
        $kernel = self::bootKernel();
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $application = new Application($kernel);
        $command = $application->find('app:lobby:message:change');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['neue Nachricht']);
        $commandTester->execute(['id' => $messageRepo->findAll()[0]->getId()]);
        $commandTester->assertCommandIsSuccessful();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $output = $commandTester->getDisplay();
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $this->assertStringContainsString('[OK] You have changed the message to neue Nachricht', $output);
        self::assertEquals(3, sizeof($messageRepo->findAll()));
        self::assertEquals(true, $messageRepo->findAll()[0]->isActive());
        self::assertEquals('neue Nachricht', $messageRepo->findAll()[0]->getText());
    }

    public function testChangeEnableLobbyMessage(): void
    {
        $kernel = self::bootKernel();
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $application = new Application($kernel);
        $command = $application->find('app:lobby:message:deactivate');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute(['id' => $messageRepo->findAll()[0]->getId()]);
        $commandTester->assertCommandIsSuccessful();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $output = $commandTester->getDisplay();
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $this->assertStringContainsString('[OK] You have DISABLED the message ', $output);
        self::assertEquals(3, sizeof($messageRepo->findAll()));
        self::assertEquals(false, $messageRepo->findAll()[0]->isActive());
    }
    public function testChangeEnableTOEnabledLobbyMessage(): void
    {
        $kernel = self::bootKernel();
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $application = new Application($kernel);
        $command = $application->find('app:lobby:message:deactivate');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute(['id' => $messageRepo->findAll()[1]->getId()]);
        $commandTester->assertCommandIsSuccessful();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $output = $commandTester->getDisplay();
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $this->assertStringContainsString('[OK] You have ENABLED the message ', $output);
        self::assertEquals(3, sizeof($messageRepo->findAll()));
        self::assertEquals(true, $messageRepo->findAll()[1]->isActive());
    }

    public function testDeleteLobbyMessage(): void
    {
        $kernel = self::bootKernel();
        $messageRepo = self::getContainer()->get(PredefinedLobbyMessagesRepository::class);
        $application = new Application($kernel);
        $command = $application->find('app:lobby:message:delete');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute(['id' => $messageRepo->findAll()[1]->getId()]);
        $commandTester->assertCommandIsSuccessful();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $output = $commandTester->getDisplay();
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $this->assertStringContainsString('[OK] We delete the message: Bitte warten/Disabled!', $output);
        self::assertEquals(2, sizeof($messageRepo->findAll()));
    }
}
