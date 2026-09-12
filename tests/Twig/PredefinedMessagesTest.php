<?php

namespace App\Tests\Twig;

use App\Entity\PredefinedLobbyMessages;
use App\Twig\PredefinedMessages;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\TwigFunction;

class PredefinedMessagesTest extends KernelTestCase
{
    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        self::bootKernel();
        $extension = self::getContainer()->get(PredefinedMessages::class);

        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertSame('getPredefinedMessages', $functions[0]->getName());
        $this->assertSame([$extension, 'getPredefinedMessages'], $functions[0]->getCallable());
    }

    public function testGetPredefinedMessagesReturnsOnlyActiveMessages(): void
    {
        self::bootKernel();
        $extension = self::getContainer()->get(PredefinedMessages::class);

        $messages = $extension->getPredefinedMessages();

        $this->assertNotEmpty($messages);
        $this->assertContainsOnlyInstancesOf(PredefinedLobbyMessages::class, $messages);
        foreach ($messages as $message) {
            $this->assertTrue($message->isActive());
        }

        $texts = array_map(static fn(PredefinedLobbyMessages $message) => $message->getText(), $messages);
        $this->assertContains('Bitte warten!', $texts);
        $this->assertContains('Wir haben andere Themen!', $texts);
        $this->assertNotContains('Bitte warten/Disabled!', $texts);
    }

    public function testGetPredefinedMessagesAreOrderedByPriority(): void
    {
        self::bootKernel();
        $extension = self::getContainer()->get(PredefinedMessages::class);

        $messages = call_user_func($extension->getFunctions()[0]->getCallable());

        $priorities = array_map(static fn(PredefinedLobbyMessages $message) => $message->getPriority(), $messages);
        $sorted = $priorities;
        sort($sorted);

        $this->assertSame($sorted, $priorities);
    }
}
