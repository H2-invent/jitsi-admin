<?php

namespace App\Tests\Twig;

use App\Twig\BrowserCompability;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\TwigFunction;

class BrowserCompabilityTest extends TestCase
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

    private function extensionForUserAgent(?string $userAgent): BrowserCompability
    {
        $requestStack = new RequestStack();
        if ($userAgent !== null) {
            $request = new Request();
            $request->headers->set('User-Agent', $userAgent);
            $requestStack->push($request);
        }

        return new BrowserCompability($requestStack);
    }

    private function functionByName(BrowserCompability $extension, string $name): string
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
        $extension = $this->extensionForUserAgent('');
        $this->assertSame(["isFirefox", "isOSType"], $this->functionNames($extension));
    }

    public function testIsFirefoxReturnsTrueForFirefoxUserAgent(): void
    {
        $extension = $this->extensionForUserAgent('Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0');

        $this->assertTrue($extension->isFirefox());
        $this->assertTrue(call_user_func([$extension, $this->functionByName($extension, 'isFirefox')]));
    }

    public function testIsFirefoxReturnsTrueForFirefoxIosUserAgent(): void
    {
        $extension = $this->extensionForUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) FxiOS/120.0 Mobile/15E148 Safari/605.1.15');

        $this->assertTrue($extension->isFirefox());
    }

    public function testIsFirefoxReturnsFalseForChromeUserAgent(): void
    {
        $extension = $this->extensionForUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

        $this->assertFalse($extension->isFirefox());
    }

    public function testIsOstypeDetectsWindows(): void
    {
        $extension = $this->extensionForUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        $this->assertTrue($extension->isOSType('windows'));
        $this->assertTrue($extension->isOSType('Windows'));
        $this->assertTrue(call_user_func([$extension, $this->functionByName($extension, 'isOSType')], 'windows'));
    }

    public function testIsOstypeDetectsMac(): void
    {
        $extension = $this->extensionForUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15');

        $this->assertTrue($extension->isOSType('mac'));
        $this->assertTrue($extension->isOSType('MAC'));
    }

    public function testIsOstypeReturnsFalseForUnknownTypeAndMismatchedUserAgent(): void
    {
        $extension = $this->extensionForUserAgent('Mozilla/5.0 (X11; Linux x86_64) Gecko/20100101 Firefox/120.0');

        $this->assertFalse($extension->isOSType('linux'));
        $this->assertFalse($extension->isOSType('windows'));
        $this->assertFalse($extension->isOSType('mac'));
    }

    public function testNoCurrentRequestReturnsFalseAndEmptyUserAgent(): void
    {
        $extension = $this->extensionForUserAgent(null);

        $this->assertFalse($extension->isFirefox());
        $this->assertFalse($extension->isOSType('windows'));

        $reflection = new \ReflectionMethod(BrowserCompability::class, 'getUserAgent');
        $reflection->setAccessible(true);
        $this->assertSame('', $reflection->invoke($extension));
    }

    public function testGetUserAgentReturnsRequestHeader(): void
    {
        $extension = $this->extensionForUserAgent('SomeCustomAgent/1.0');

        $reflection = new \ReflectionMethod(BrowserCompability::class, 'getUserAgent');
        $reflection->setAccessible(true);
        $this->assertSame('SomeCustomAgent/1.0', $reflection->invoke($extension));
    }
}
