<?php

namespace App\Tests\Twig;

use App\Twig\ColorUtils;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

class ColorUtilsTest extends TestCase
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

    public function testGetFiltersReturnsExpectedTwigFilter(): void
    {
        $extension = new ColorUtils();
        $this->assertSame(["color_lighten"], $this->filterNames($extension));
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
        $this->assertSame(["color_lighten"], $this->filterNames($extension));
    }
}
