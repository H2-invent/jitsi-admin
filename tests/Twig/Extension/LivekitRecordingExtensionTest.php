<?php

namespace App\Tests\Twig\Extension;

use App\Twig\Extension\LivekitRecordingExtension;
use App\Twig\Runtime\LivekitRecordingRuntime;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;
use Twig\TwigFunction;

class LivekitRecordingExtensionTest extends TestCase
{
    public function testGetFiltersRegistersExpectedFilter(): void
    {
        $extension = new LivekitRecordingExtension();

        $filters = $extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertSame('filter_name', $filters[0]->getName());
        $this->assertSame([LivekitRecordingRuntime::class, 'doSomething'], $filters[0]->getCallable());
    }

    public function testGetFunctionsRegistersExpectedFunction(): void
    {
        $extension = new LivekitRecordingExtension();

        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertSame('getRecordingForRoomAndUser', $functions[0]->getName());
        $this->assertSame([LivekitRecordingRuntime::class, 'getRecordingForRoomAndUser'], $functions[0]->getCallable());
    }
}
