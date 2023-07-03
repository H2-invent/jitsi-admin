<?php

namespace App\Command\Installer;

class BasicConfig implements ConvertToEnvironmentInterface
{
    use ConvertToEnvironmentTrait;

    private const ENVIRONMENT = [
        'MERCURE_URL' => 'mercureUrl',
        'MERCURE_PUBLIC_URL' => 'baseUrl',
        'MERCURE_JWT_SECRET' => 'secret',
        'WEBSOCKET_SECRET' => 'secret',
        'VICH_BASE' => 'baseUrl',
        'laF_baseUrl' => 'baseUrl',
    ];

    private function __construct(
        private string $baseUrl = '',
        private string $secret = '',
        private string $mercureUrl = 'http://localhost:3000',
    )
    {
    }

    public static function createFromParameters(string $baseUrl, ?string $secret): self
    {
        return new self(
            baseUrl: $baseUrl,
            secret: $secret ?? md5(uniqid()),
        );
    }

    public function getEnvironmentMap(): array
    {
        return self::ENVIRONMENT;
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function secret(): string
    {
        return $this->secret;
    }

    public function mercureUrl(): string
    {
        return $this->mercureUrl . '/.well-known/mercure';
    }
}
