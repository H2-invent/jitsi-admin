<?php

namespace App\Tests\Twig;

use App\Twig\Nl2liExtension;
use PHPUnit\Framework\TestCase;
use Twig\Node\Node;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Nl2liExtensionTest extends TestCase
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

    public function testGetFiltersReturnsSafeHtmlFilter(): void
    {
        $extension = new Nl2liExtension();
        $this->assertSame(["nl2li"], $this->filterNames($extension));
    }

    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        $extension = new Nl2liExtension();
        $this->assertSame(["nl2li"], $this->functionNames($extension));
    }

    public function testNl2liWrapsEachLineInListItem(): void
    {
        $extension = new Nl2liExtension();

        $this->assertSame('<li>a</li><li>b</li><li>c</li>', $extension->nl2li("a\nb\nc"));
    }

    public function testNl2liWithSingleLine(): void
    {
        $extension = new Nl2liExtension();

        $this->assertSame('<li>only line</li>', $extension->nl2li('only line'));
    }

    public function testNl2liViaRegisteredCallables(): void
    {
        $extension = new Nl2liExtension();

        $this->assertSame('<li>a</li><li>b</li>', call_user_func([$extension, 'nl2li'], "a\nb"));
        $this->assertSame('<li>a</li><li>b</li>', call_user_func([$extension, 'nl2li'], "a\nb"));
    }
}
