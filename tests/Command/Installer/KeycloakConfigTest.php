<?php

namespace App\Tests\Command\Installer;

use App\Command\Installer\KeycloakConfig;
use PHPUnit\Framework\TestCase;

class KeycloakConfigTest extends TestCase
{
    public function testVersion19AppendsAuthPath(): void
    {
        $config = KeycloakConfig::createFromParameters('https://keycloak.example.org', 'master', 19, 'client', 'secret');

        self::assertSame('https://keycloak.example.org/auth', $config->url());
        self::assertSame('master', $config->realm());
        self::assertSame(19, $config->version());
        self::assertSame('client', $config->clientId());
        self::assertSame('secret', $config->clientSecret());
    }

    public function testVersionAbove19DoesNotAppendAuthPath(): void
    {
        $config = KeycloakConfig::createFromParameters('https://keycloak.example.org', 'master', 20, 'client', 'secret');

        self::assertSame('https://keycloak.example.org', $config->url());
    }

    public function testAuthSuffixIsStrippedFromUrl(): void
    {
        $config = KeycloakConfig::createFromParameters('https://keycloak.example.org/auth', 'master', 20, 'client', 'secret');

        self::assertSame('https://keycloak.example.org', $config->url());
    }

    public function testEnvironmentMapAndEnvironmentOutput(): void
    {
        $config = KeycloakConfig::createFromParameters('https://keycloak.example.org', 'master', 20, 'client', 'secret');

        self::assertSame([
            'OAUTH_KEYCLOAK_CLIENT_ID' => 'clientId',
            'OAUTH_KEYCLOAK_CLIENT_SECRET' => 'clientSecret',
            'OAUTH_KEYCLOAK_SERVER' => 'url',
            'OAUTH_KEYCLOAK_REALM' => 'realm',
        ], $config->getEnvironmentMap());

        $environment = $config->getAsEnvironment();
        self::assertContains('OAUTH_KEYCLOAK_CLIENT_ID="client"' . PHP_EOL, $environment);
        self::assertContains('OAUTH_KEYCLOAK_CLIENT_SECRET="secret"' . PHP_EOL, $environment);
        self::assertContains('OAUTH_KEYCLOAK_SERVER="https://keycloak.example.org"' . PHP_EOL, $environment);
        self::assertContains('OAUTH_KEYCLOAK_REALM="master"' . PHP_EOL, $environment);
    }
}
