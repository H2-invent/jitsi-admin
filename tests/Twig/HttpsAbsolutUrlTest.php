<?php

namespace App\Tests\Twig;

use App\Entity\Rooms;
use App\Service\CreateHttpsUrl;
use App\Twig\HttpsAbsolutUrl;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\TwigFunction;

class HttpsAbsolutUrlTest extends TestCase
{
    private function createExtension(array $parameters = []): HttpsAbsolutUrl
    {
        $parameterBag = new ParameterBag($parameters + [
            'laF_baseUrl' => 'https://example.com/',
            'LAF_DEV_URL' => '',
        ]);

        $createHttpsUrl = new CreateHttpsUrl(new NullLogger(), new RequestStack(), $parameterBag);

        return new HttpsAbsolutUrl($createHttpsUrl, $parameterBag);
    }

    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        $extension = $this->createExtension();

        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertSame('httpsAbolutUrl', $functions[0]->getName());
        $this->assertSame([$extension, 'httpsAbolutUrl'], $functions[0]->getCallable());
    }

    public function testUrlContainingBaseUrlIsReturnedAsAbsoluteUrl(): void
    {
        $extension = $this->createExtension();

        $this->assertSame('https://example.com/foo', $extension->httpsAbolutUrl('https://example.com/foo'));
    }

    public function testRoomHostUrlIsUsedAndUpgradedToHttps(): void
    {
        $extension = $this->createExtension();
        $room = (new Rooms())->setHostUrl('http://room.example.org/base/');

        $this->assertSame('https://room.example.org/base/meeting', $extension->httpsAbolutUrl('meeting', $room));
    }

    public function testRoomWithoutHostUrlUsesConfiguredBaseUrl(): void
    {
        $extension = $this->createExtension();

        $this->assertSame('https://example.com/join', $extension->httpsAbolutUrl('join', new Rooms()));
    }

    public function testDevUrlIsPreferredWhenConfigured(): void
    {
        $extension = $this->createExtension(['LAF_DEV_URL' => 'https://dev.example.org/']);

        $this->assertSame('https://dev.example.org/join', $extension->httpsAbolutUrl('join'));
    }

    public function testWithoutRoomOrRequestConfiguredBaseUrlIsUsed(): void
    {
        $extension = $this->createExtension();

        $this->assertSame('https://example.com/join', call_user_func($extension->getFunctions()[0]->getCallable(), 'join'));
    }
}
