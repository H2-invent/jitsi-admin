<?php

namespace App\Tests\Command\Installer;

use App\Command\Installer\SmtpConfig;
use PHPUnit\Framework\TestCase;

class SmtpConfigTest extends TestCase
{
    public function testCreateFromParametersEncodesAndDecodes(): void
    {
        $config = SmtpConfig::createFromParameters('smtp host', 587, 'mail user', 'p@ss word', 'sender@example.org');

        self::assertSame('smtp host', $config->host());
        self::assertSame(587, $config->port());
        self::assertSame('mail user', $config->username());
        self::assertSame('p@ss word', $config->password());
        self::assertSame('sender@example.org', $config->sender());
        self::assertSame('smtp://mail+user:p%40ss+word@smtp+host:587', $config->dsn());
    }

    public function testCreateFromDsnAndEmail(): void
    {
        $config = SmtpConfig::createFromDsnAndEmail('smtp://user:pass@mail.example.org:587', 'sender@example.org');

        self::assertSame('mail.example.org', $config->host());
        self::assertSame(587, $config->port());
        self::assertSame('user', $config->username());
        self::assertSame('pass', $config->password());
        self::assertSame('sender@example.org', $config->sender());
        self::assertSame('smtp://user:pass@mail.example.org:587', $config->dsn());
    }

    public function testEnvironmentMapAndEnvironmentOutput(): void
    {
        $config = SmtpConfig::createFromDsnAndEmail('smtp://user:pass@mail.example.org:587', 'sender@example.org');

        self::assertSame([
            'MAILER_DSN' => 'dsn',
            'DEFAULT_EMAIL' => 'sender',
        ], $config->getEnvironmentMap());

        $environment = $config->getAsEnvironment();
        self::assertContains('MAILER_DSN="smtp://user:pass@mail.example.org:587"' . PHP_EOL, $environment);
        self::assertContains('DEFAULT_EMAIL="sender@example.org"' . PHP_EOL, $environment);
    }
}
