<?php

namespace App\Tests\Command\Installer;

use App\Command\Installer\BasicConfig;
use PHPUnit\Framework\TestCase;

class BasicConfigTest extends TestCase
{
    public function testCreateFromParametersWithGeneratedSecret(): void
    {
        $config = BasicConfig::createFromParameters('https://example.org', null);

        self::assertSame('https://example.org', $config->baseUrl());
        self::assertNotSame('', $config->secret());
        self::assertSame(32, strlen($config->secret()));
    }

    public function testCreateFromParametersKeepsGivenSecret(): void
    {
        $config = BasicConfig::createFromParameters('https://example.org', 'my-secret');

        self::assertSame('https://example.org', $config->baseUrl());
        self::assertSame('my-secret', $config->secret());
    }

    public function testMercureUrl(): void
    {
        $config = BasicConfig::createFromParameters('https://example.org', 'secret');

        self::assertSame('http://localhost:3000/.well-known/mercure', $config->mercureUrl());
    }

    public function testEnvironmentMapAndEnvironmentOutput(): void
    {
        $config = BasicConfig::createFromParameters('https://example.org', 'secret');

        self::assertSame([
            'MERCURE_URL' => 'mercureUrl',
            'MERCURE_PUBLIC_URL' => 'baseUrl',
            'MERCURE_JWT_SECRET' => 'secret',
            'WEBSOCKET_SECRET' => 'secret',
            'VICH_BASE' => 'baseUrl',
            'laF_baseUrl' => 'baseUrl',
        ], $config->getEnvironmentMap());

        $environment = $config->getAsEnvironment();
        self::assertCount(6, $environment);
        self::assertContains('MERCURE_URL="http://localhost:3000/.well-known/mercure"' . PHP_EOL, $environment);
        self::assertContains('MERCURE_PUBLIC_URL="https://example.org"' . PHP_EOL, $environment);
        self::assertContains('MERCURE_JWT_SECRET="secret"' . PHP_EOL, $environment);
        self::assertContains('WEBSOCKET_SECRET="secret"' . PHP_EOL, $environment);
        self::assertContains('VICH_BASE="https://example.org"' . PHP_EOL, $environment);
        self::assertContains('laF_baseUrl="https://example.org"' . PHP_EOL, $environment);
    }
}
