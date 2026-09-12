<?php

namespace App\Tests\Twig;

use App\Entity\Rooms;
use App\Twig\Theme;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\TwigFunction;

class ThemeTest extends KernelTestCase
{
    private function extension(): Theme
    {
        return self::getContainer()->get(Theme::class);
    }

    private function functionByName(Theme $extension, string $name): TwigFunction
    {
        foreach ($extension->getFunctions() as $function) {
            if ($function->getName() === $name) {
                return $function;
            }
        }
        self::fail(sprintf('Twig function "%s" not registered', $name));
    }

    public function testGetFunctionsReturnsExpectedTwigFunctions(): void
    {
        self::bootKernel();
        $extension = $this->extension();

        $functions = $extension->getFunctions();
        $names = array_map(static fn(TwigFunction $function) => $function->getName(), $functions);

        $this->assertSame(['getThemeProperties', 'getApplicationProperties'], $names);
        $this->assertSame([$extension, 'getThemeProperties'], $functions[0]->getCallable());
        $this->assertSame([$extension, 'getApplicationProperties'], $functions[1]->getCallable());
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
            call_user_func($this->functionByName($extension, 'getApplicationProperties')->getCallable(), 'laf_icon_mapping_search')
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
