<?php

declare(strict_types=1);

namespace App\Command\Installer;

class KeycloakConfig implements ConvertToEnvironmentInterface
{
    use ConvertToEnvironmentTrait;

    private const array ENVIRONMENT = [
        'OAUTH_KEYCLOAK_CLIENT_ID'     => 'clientId',
        'OAUTH_KEYCLOAK_CLIENT_SECRET' => 'clientSecret',
        'OAUTH_KEYCLOAK_SERVER'        => 'url',
        'OAUTH_KEYCLOAK_REALM'         => 'realm',
    ];

    private function __construct(
        private readonly string $url,
        private readonly int    $version,
        private readonly string $realm,
        private readonly string $clientId,
        private readonly string $clientSecret,
    ) {
    }

    public static function createFromParameters(
        string $url,
        string $realm,
        int    $version,
        string $clientId,
        string $clientSecret,
    ): self {
        // Keycloak < 20 serves everything below /auth; url() appends it again
        if (str_ends_with($url, '/auth')) {
            $url = str_replace('/auth', '', $url);
        }

        return new self(
            url: $url,
            version: $version,
            realm: $realm,
            clientId: $clientId,
            clientSecret: $clientSecret,
        );
    }

    /**
     * @return array<string, string>
     */
    public function getEnvironmentMap(): array
    {
        return self::ENVIRONMENT;
    }

    public function url(): string
    {
        if ($this->version > 19) {
            return $this->url;
        }

        return $this->url . '/auth';
    }

    public function realm(): string
    {
        return $this->realm;
    }

    public function clientId(): string
    {
        return $this->clientId;
    }

    public function clientSecret(): string
    {
        return $this->clientSecret;
    }

    public function version(): int
    {
        return $this->version;
    }
}
