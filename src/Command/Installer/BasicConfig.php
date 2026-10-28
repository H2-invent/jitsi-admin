<?php

namespace App\Command\Installer;

use App\Enums\UidType;
use App\Helper\UidGeneratorHelper;

class BasicConfig implements ConvertToEnvironmentInterface
{
    use ConvertToEnvironmentTrait;

    private const array ENVIRONMENT = [
        'MERCURE_URL'        => 'mercureUrl',
        'MERCURE_PUBLIC_URL' => 'baseUrl',
        'MERCURE_JWT_SECRET' => 'secret',
        'WEBSOCKET_SECRET'   => 'secret',
        'VICH_BASE'          => 'baseUrl',
        'laF_baseUrl'        => 'baseUrl',
    ];

    private function __construct(
        private readonly string $baseUrl = '',
        private readonly string $secret = '',
        private readonly string $mercureUrl = 'http://localhost:3000',
    ) {
    }

    public static function createFromParameters(string $baseUrl, ?string $secret): self
    {
        return new self(
            baseUrl: $baseUrl,
            secret: $secret ?? UidGeneratorHelper::generate(UidType::SECRET),
        );
    }

    /**
     * @return array<string, string>
     */
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
