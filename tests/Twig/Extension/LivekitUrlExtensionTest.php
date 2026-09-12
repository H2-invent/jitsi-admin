<?php

namespace App\Tests\Twig\Extension;

use App\Twig\Extension\LivekitUrlExtension;
use App\Twig\Runtime\LivekitUrlRuntime;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class LivekitUrlExtensionTest extends TestCase
{
    public function testGetFunctionsRegistersExpectedFunction(): void
    {
        $extension = new LivekitUrlExtension();

        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertSame('getLiveKitName', $functions[0]->getName());
        $this->assertSame([LivekitUrlRuntime::class, 'getLiveKitName'], $functions[0]->getCallable());
    }
}
