<?php

namespace App\Tests\Twig;

use App\Entity\PredefinedLobbyMessages;
use App\Twig\PredefinedMessages;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\TwigFunction;

class PredefinedMessagesTest extends KernelTestCase
{

    private function functionNames(object $extension): array
    {
        $names = [];
        foreach ((new \ReflectionClass($extension))->getMethods() as $method) {
            foreach ($method->getAttributes(\Twig\Attribute\AsTwigFunction::class) as $attribute) {
                $names[] = $attribute->newInstance()->name;
            }
        }
        return $names;
    }

    private function filterNames(object $extension): array
    {
        $names = [];
        foreach ((new \ReflectionClass($extension))->getMethods() as $method) {
            foreach ($method->getAttributes(\Twig\Attribute\AsTwigFilter::class) as $attribute) {
                $names[] = $attribute->newInstance()->name;
            }
        }
        return $names;
    }

    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        $extension = self::getContainer()->get(PredefinedMessages::class);
        $this->assertSame(["getPredefinedMessages"], $this->functionNames($extension));
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

        $messages = $extension->getPredefinedMessages();

        $priorities = array_map(static fn(PredefinedLobbyMessages $message) => $message->getPriority(), $messages);
        $sorted = $priorities;
        sort($sorted);

        $this->assertSame($sorted, $priorities);
    }
}
