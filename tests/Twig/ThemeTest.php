<?php

namespace App\Tests\Twig;

use App\Entity\Rooms;
use App\Twig\Theme;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\TwigFunction;

class ThemeTest extends KernelTestCase
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

    private function extension(): Theme
    {
        return self::getContainer()->get(Theme::class);
    }

    private function functionByName(Theme $extension, string $name): string
    {
        foreach ((new \ReflectionClass($extension))->getMethods() as $method) {
            foreach ($method->getAttributes(\Twig\Attribute\AsTwigFunction::class) as $attribute) {
                if ($attribute->newInstance()->name === $name) {
                    return $method->getName();
                }
            }
        }
        self::fail(sprintf('Twig Function "%s" not registered', $name));
    }

    public function testGetFunctionsReturnsExpectedTwigFunctions(): void
    {
        $extension = $this->extension();
        $this->assertSame(["getThemeProperties", "getApplicationProperties"], $this->functionNames($extension));
    }

    public function testGetThemePropertiesReturnsFalseWithoutRequestAndWithoutThemeFile(): void
    {
        self::bootKernel();
        $extension = $this->extension();

        $this->assertFalse($extension->getThemeProperties());
        $this->assertFalse($extension->getThemeProperties((new Rooms())->setHostUrl('http://localhost:8000')));
    }

    public function testGetApplicationPropertiesDecodesJsonParameter(): void
    {
        self::bootKernel();
        $extension = $this->extension();

        $this->assertSame(
            ['telephoneNumber' => 'fa fa-phone'],
            $extension->getApplicationProperties('laf_icon_mapping_search')
        );
        $this->assertSame(
            ['telephoneNumber' => 'fa fa-phone'],
            call_user_func([$extension, $this->functionByName($extension, 'getApplicationProperties')], 'laf_icon_mapping_search')
        );
    }

    public function testGetApplicationPropertiesReturnsRawValueForNonJsonParameter(): void
    {
        self::bootKernel();
        $extension = $this->extension();
        $expected = self::getContainer()->getParameter('laf_showName');

        $this->assertIsString($expected);
        $this->assertStringContainsString('user.specialField.ou$', $expected);
        $this->assertSame($expected, $extension->getApplicationProperties('laf_showName'));
    }

    public function testGetApplicationPropertiesReturnsNullForUnknownParameter(): void
    {
        self::bootKernel();
        $extension = $this->extension();

        $this->assertNull($extension->getApplicationProperties('THIS_PARAMETER_DOES_NOT_EXIST'));
    }
}
