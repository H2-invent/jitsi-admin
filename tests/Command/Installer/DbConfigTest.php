<?php

namespace App\Tests\Command\Installer;

use App\Command\Installer\DbConfig;
use PHPUnit\Framework\TestCase;

class DbConfigTest extends TestCase
{
    public function testCreateFromDefault(): void
    {
        $config = DbConfig::createFromDefault();

        self::assertSame('mysql://jitsiadmin:jitsiadmin@localhost:3306/jitsi-admin?serverVersion=5.7', $config->dsn());
        self::assertSame('jitsiadmin', $config->username());
        self::assertSame('jitsiadmin', $config->password());
        self::assertSame('localhost', $config->host());
        self::assertSame(3306, $config->port());
        self::assertSame('jitsi-admin', $config->database());
        self::assertSame('5.7', $config->serverVersion());
    }

    public function testCreateFromParametersEncodesAndDecodes(): void
    {
        $config = DbConfig::createFromParameters('mysql', '8.0', 'db host', 3307, 'my db', 'user name', 'p@ss word');

        self::assertSame('db host', $config->host());
        self::assertSame('my db', $config->database());
        self::assertSame('user name', $config->username());
        self::assertSame('p@ss word', $config->password());
        self::assertSame(3307, $config->port());
        self::assertSame('8.0', $config->serverVersion());
        self::assertSame('mysql://user+name:p%40ss+word@db+host:3307/my+db?serverVersion=8.0', $config->dsn());
    }

    public function testCreateFromDsn(): void
    {
        $config = DbConfig::createFromDsn('mysql://jitsiadmin:secret@localhost:3306/jitsi-admin?serverVersion=5.7');

        self::assertSame('jitsiadmin', $config->username());
        self::assertSame('secret', $config->password());
        self::assertSame('localhost', $config->host());
        self::assertSame(3306, $config->port());
        self::assertSame('jitsi-admin', $config->database());
        self::assertSame('5.7', $config->serverVersion());
        self::assertSame('mysql://jitsiadmin:secret@localhost:3306/jitsi-admin?serverVersion=5.7', $config->dsn());
    }

    public function testEnvironmentMapAndEnvironmentOutput(): void
    {
        $config = DbConfig::createFromDefault();

        self::assertSame(['DATABASE_URL' => 'dsn'], $config->getEnvironmentMap());
        self::assertSame(
            ['DATABASE_URL="mysql://jitsiadmin:jitsiadmin@localhost:3306/jitsi-admin?serverVersion=5.7"' . PHP_EOL],
            $config->getAsEnvironment()
        );
    }
}
