<?php

namespace App\Tests\Command\Installer;

use App\Command\Installer\BasicConfig;
use App\Command\Installer\ConvertToEnvironmentTrait;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ConvertToEnvironmentTraitTest extends TestCase
{
    public function testGetAsEnvironmentUsesEnvironmentMapAndGetters(): void
    {
        $config = BasicConfig::createFromParameters('https://example.org', 'secret');

        $environment = $config->getAsEnvironment();

        self::assertCount(6, $environment);
        self::assertContains('MERCURE_PUBLIC_URL="https://example.org"' . PHP_EOL, $environment);
        self::assertContains('MERCURE_JWT_SECRET="secret"' . PHP_EOL, $environment);
        self::assertContains('laF_baseUrl="https://example.org"' . PHP_EOL, $environment);
    }

    public function testGetAsEnvironmentThrowsWhenInterfaceIsMissing(): void
    {
        $subject = new class {
            use ConvertToEnvironmentTrait;
        };

        $this->expectException(RuntimeException::class);
        $subject->getAsEnvironment();
    }
}
