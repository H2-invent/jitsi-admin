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
            host: $host,
            port: $port,
            username: $username,
            password: $password,
            sender: $sender,
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
}