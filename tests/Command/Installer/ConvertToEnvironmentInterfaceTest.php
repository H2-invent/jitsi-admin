<?php

namespace App\Tests\Command\Installer;

use App\Command\Installer\BasicConfig;
use App\Command\Installer\ConvertToEnvironmentInterface;
use App\Command\Installer\DbConfig;
use App\Command\Installer\KeycloakConfig;
use App\Command\Installer\SmtpConfig;
use PHPUnit\Framework\TestCase;

class ConvertToEnvironmentInterfaceTest extends TestCase
{
    public function testConfigClassesImplementTheInterface(): void
    {
        self::assertInstanceOf(ConvertToEnvironmentInterface::class, BasicConfig::createFromParameters('https://example.org', 'secret'));
        self::assertInstanceOf(ConvertToEnvironmentInterface::class, DbConfig::createFromDefault());
        self::assertInstanceOf(ConvertToEnvironmentInterface::class, KeycloakConfig::createFromParameters('https://keycloak.example.org', 'master', 20, 'client', 'secret'));
        self::assertInstanceOf(ConvertToEnvironmentInterface::class, SmtpConfig::createFromParameters('smtp.example.org', 25, 'user', 'pass', 'sender@example.org'));
    }

    public function testInterfaceDeclaresExpectedMethods(): void
    {
        $reflection = new \ReflectionClass(ConvertToEnvironmentInterface::class);

        self::assertTrue($reflection->hasMethod('getAsEnvironment'));
        self::assertTrue($reflection->hasMethod('getEnvironmentMap'));
    }
}
