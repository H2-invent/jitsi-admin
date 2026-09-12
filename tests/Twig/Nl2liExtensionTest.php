<?php

namespace App\Tests\Twig;

use App\Twig\Nl2liExtension;
use PHPUnit\Framework\TestCase;
use Twig\Node\Node;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Nl2liExtensionTest extends TestCase
{
    public function testGetFiltersReturnsSafeHtmlFilter(): void
    {
        $extension = new Nl2liExtension();

        $filters = $extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertSame('nl2li', $filters[0]->getName());
        $this->assertSame([$extension, 'nl2li'], $filters[0]->getCallable());
        $this->assertSame(['html'], $filters[0]->getSafe(new Node()));
    }

    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        $extension = new Nl2liExtension();

        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertSame('nl2li', $functions[0]->getName());
        $this->assertSame([$extension, 'nl2li'], $functions[0]->getCallable());
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

        $this->assertSame('<li>a</li><li>b</li>', call_user_func($extension->getFilters()[0]->getCallable(), "a\nb"));
        $this->assertSame('<li>a</li><li>b</li>', call_user_func($extension->getFunctions()[0]->getCallable(), "a\nb"));
    }
}
