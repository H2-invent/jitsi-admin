<?php

declare(strict_types=1);

namespace App\Command\Installer;

class SmtpConfig implements ConvertToEnvironmentInterface
{
    use ConvertToEnvironmentTrait;

    private static string $DSN = 'smtp://%s:%s@%s:%d';

    private const ENVIRONMENT = [
        'MAILER_DSN' => 'dsn',
        'DEFAULT_EMAIL' => 'sender',
    ];

    private function __construct(
        private string $host,
        private int    $port,
        private string $username,
        private string $password,
        private string $sender,
    )
    {
    }

    public static function createFromParameters(
        string $host,
        int    $port,
        string $username,
        string $password,
        string $sender,
    ): self
    {
        return new self(
            host: urlencode($host),
            port: $port,
            username: urlencode($username),
            password: urlencode($password),
            sender: $sender,
        );
    }

    public static function createFromDsnAndEmail(string $dsn, string $email): self
    {
        $smtp = [];
        preg_match('~.*://(?<username>.*):(?<password>.*)@(?<host>.*):(?<port>\d*)~', $dsn, $smtp);

        return new self(
            host: $smtp['host'],
            port: (int)$smtp['port'],
            username: $smtp['username'],
            password: $smtp['password'],
            sender: $email,
        );
    }

    public function getEnvironmentMap(): array
    {
        return self::ENVIRONMENT;
    }

    public function dsn(): string
    {
        return sprintf(
            self::$DSN,
            $this->username,
            $this->password,
            $this->host,
            $this->port
        );
    }

    public function sender(): string
    {
        return $this->sender;
    }

    public function host(): string
    {
        return urldecode($this->host);
    }

    public function port(): int
    {
        return $this->port;
    }

    public function username(): string
    {
        return urldecode($this->username);
    }

    public function password(): string
    {
        return urldecode($this->password);
    }
}
