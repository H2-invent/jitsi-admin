<?php

namespace App\Tests\Service;

use App\Service\PexelService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class PexelServiceTest extends TestCase
{
    protected function setUp(): void
    {
        (new FilesystemAdapter())->delete('pexels_image');
    }

    public function testGetImageFromPexelsReturnsDecodedPhoto(): void
    {
        $calls = 0;
        $client = new MockHttpClient(function () use (&$calls) {
            $calls++;
            return new MockResponse(json_encode([
                'photos' => [
                    ['id' => 11, 'src' => ['large' => 'https://images.example/11.jpg']],
                    ['id' => 22, 'src' => ['large' => 'https://images.example/22.jpg']],
                ],
            ]));
        });

        $service = new PexelService($client, new ParameterBag([
            'laF_pexel_api_key' => 'secret',
            'enterprise_noExternal' => 0,
            'laF_pexel_refresh_time' => 60,
        ]));

        $image = $service->getImageFromPexels();

        self::assertSame(1, $calls);
        self::assertIsArray($image);
        self::assertContains($image['id'], [11, 22]);
        self::assertArrayHasKey('src', $image);
    }

    public function testGetImageFromPexelsServesSecondCallFromCache(): void
    {
        $service = new PexelService($this->clientReturning('https://images.example/11.jpg', 11), $this->parameters());

        $first = $service->getImageFromPexels();

        $failingClient = new MockHttpClient(function () {
            throw new \RuntimeException('HTTP client must not be called when the value is cached');
        });
        $second = (new PexelService($failingClient, $this->parameters()))->getImageFromPexels();

        self::assertSame($first, $second);
        self::assertSame(11, $second['id']);
    }

    public function testGetImageFromPexelsReturnsNullWithoutApiKey(): void
    {
        $calls = 0;
        $client = new MockHttpClient(function () use (&$calls) {
            $calls++;
            return new MockResponse('{}');
        });

        $service = new PexelService($client, new ParameterBag([
            'laF_pexel_api_key' => '',
            'enterprise_noExternal' => 0,
            'laF_pexel_refresh_time' => 60,
        ]));

        self::assertNull($service->getImageFromPexels());
        self::assertSame(0, $calls);
    }

    public function testGetImageFromPexelsReturnsNullWhenExternalAccessDisabled(): void
    {
        $calls = 0;
        $client = new MockHttpClient(function () use (&$calls) {
            $calls++;
            return new MockResponse('{}');
        });

        $service = new PexelService($client, new ParameterBag([
            'laF_pexel_api_key' => 'secret',
            'enterprise_noExternal' => 1,
            'laF_pexel_refresh_time' => 60,
        ]));

        self::assertNull($service->getImageFromPexels());
        self::assertSame(0, $calls);
    }

    private function parameters(): ParameterBag
    {
        return new ParameterBag([
            'laF_pexel_api_key' => 'secret',
            'enterprise_noExternal' => 0,
            'laF_pexel_refresh_time' => 60,
        ]);
    }

    private function clientReturning(string $url, int $id): MockHttpClient
    {
        return new MockHttpClient(new MockResponse(json_encode([
            'photos' => [
                ['id' => $id, 'src' => ['large' => $url]],
            ],
        ])));
    }
}
