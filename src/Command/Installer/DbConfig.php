<?php

declare(strict_types=1);

namespace App\Command\Installer;

class DbConfig implements ConvertToEnvironmentInterface
{
    use ConvertToEnvironmentTrait;

    private static string $DNS_FORMAT = '%s://%s:%s@%s:%d/%s?serverVersion=%s';

    private const ENVIRONMENT = [
        'DATABASE_URL' => 'dsn',
    ];

    private function __construct(
        private string $engine,
        private string $serverVersion,
        private string $host,
        private int    $port,
        private string $database,
        private string $username = 'jitsiadmin',
        private string $password = 'jitsiadmin',
    )
    {
    }

    public static function createFromParameters(
        string $engine,
        string $serverVersion,
        string $host,
        int    $port,
        string $database,
        string $username,
        string $password,
    ): self
    {
        return new self(
            engine: urlencode($engine),
            serverVersion: $serverVersion,
            host: urlencode($host),
            port: $port,
            database: urlencode($database),
            username: urlencode($username),
            password: urlencode($password),
        );
    }

    public static function createFromDefault(): self
    {
        return new self(
            engine: 'mysql',
            serverVersion: '5.7',
            host: 'localhost',
            port: 3306,
            database: 'jitsi-admin',
            username: 'jitsiadmin',
            password: 'jitsiadmin',
        );
    }

    public static function createFromDsn(string $dsn): self
    {
        $dbConfig = [];
        preg_match('~.*://(?<username>.*):(?<password>.*)@(?<host>.*):(?<port>\d*)/(?<database>.*)\?serverVersion=(?<serverVersion>.*)~', $dsn, $dbConfig);
        return new self(
            engine: 'mysql',
            serverVersion: $dbConfig['serverVersion'],
            host: $dbConfig['host'],
            port: (int)$dbConfig['port'],
            database: $dbConfig['database'],
            username: $dbConfig['username'],
            password: $dbConfig['password'],
        );
    }

    public function getEnvironmentMap(): array
    {
        return self::ENVIRONMENT;
    }

    public function dsn(): string
    {
        return sprintf(
            self::$DNS_FORMAT,
            $this->engine,
            $this->username,
            $this->password,
            $this->host,
            $this->port,
            $this->database,
            $this->serverVersion,
        );
    }

    public function username(): string
    {
        return urldecode($this->username);
    }

    public function password(): string
    {
        return urldecode($this->password);
    }

    public function host(): string
    {
        return urldecode($this->host);
    }

    public function port(): int
    {
        return $this->port;
    }

    public function database(): string
    {
        return urldecode($this->database);
    }

    public function serverVersion(): string
    {
        return $this->serverVersion;
    }
}
