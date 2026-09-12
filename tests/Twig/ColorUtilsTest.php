<?php

namespace App\Tests\Twig;

use App\Twig\ColorUtils;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

class ColorUtilsTest extends TestCase
{
    public function testGetFiltersReturnsExpectedTwigFilter(): void
    {
        $extension = new ColorUtils();

        $filters = $extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertSame('color_lighten', $filters[0]->getName());
        $this->assertSame([$extension, 'color_lighten'], $filters[0]->getCallable());
    }

    public function testColorLightenBrightensHexColor(): void
    {
        $extension = new ColorUtils();

        $this->assertSame('#1a1a1a', $extension->color_lighten('#000000', 10));
        $this->assertSame('#ff1a1a', $extension->color_lighten('#ff0000', 10));
    }

    public function testColorLightenTrimsWhitespace(): void
    {
        $extension = new ColorUtils();

        $this->assertSame('#1a1a1a', $extension->color_lighten('  #000000  ', 10));
    }

    public function testColorLightenReturnsOriginalValueOnInvalidColor(): void
    {
        $extension = new ColorUtils();

        $this->assertSame('not-a-color', $extension->color_lighten('not-a-color', 10));
        $this->assertSame('', $extension->color_lighten('', 10));
    }

    public function testColorLightenViaRegisteredFilterCallable(): void
    {
        $extension = new ColorUtils();
        $filter = $extension->getFilters()[0];

        $this->assertSame('#1a1a1a', call_user_func($filter->getCallable(), '#000000', 10));
    }
}
