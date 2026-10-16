<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class RabbitMqDsnEnvVarProcessor implements EnvVarProcessorInterface
{
    private const CACERT_PATH = '/ca_certificate.pem';
    private const CLIENT_CERT_PATH = '/client_certificate.pem';
    private const CLIENT_KEY_PATH = '/client_key.pem';

    public static function getProvidedTypes(): array
    {
        return ['rabbitmq_dsn' => 'string'];
    }

    public function getEnv(string $prefix, string $name, \Closure $getEnv): string
    {
        $baseDsn = $getEnv($name);
        $tlsMode = strtolower($getEnv('RABBITMQ_TLS_MODE'));

        $parsedUrl = parse_url($baseDsn);
        if ($parsedUrl === false) {
            throw new \InvalidArgumentException("Invalid DSN format in $name");
        }

        return $this->buildDsn($parsedUrl, $tlsMode);
    }

    private function buildDsn(array $parsedUrl, string $tlsMode): string
    {
        $scheme = match ($tlsMode) {
            'none' => 'amqp',
            'tls', 'mtls' => 'amqps',
            default => throw new \InvalidArgumentException(
                "Invalid RABBITMQ_TLS_MODE value: '{$tlsMode}'. Valid values are: none, tls, mtls"
            ),
        };

        if (!isset($parsedUrl['user'], $parsedUrl['pass'], $parsedUrl['host'], $parsedUrl['path'])) {
            throw new \InvalidArgumentException('RABBITMQ_DSN is missing something. Needs at least amqp://user:pass@host/path');
        }

        $portParam = [];
        // when using TLS we have to give the port as query param, don't ask me why
        if ($scheme === 'amqps') {
            $portParam['port'] = $parsedUrl['port'];
            unset($parsedUrl['port']);
        }

        $dsn = "{$scheme}://{$parsedUrl['user']}:{$parsedUrl['pass']}@{$parsedUrl['host']}";

        if (isset($parsedUrl['port'])) {
            $dsn .= ":{$parsedUrl['port']}";
        }

        $dsn .= $parsedUrl['path'];

        $parsedQuery = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $parsedQuery);
        }

        $queryParams = array_merge(
            $portParam,
            $parsedQuery,
            $this->getTlsQueryParams($tlsMode),
        );
        if ($queryParams !== []) {
            $dsn .= '?' . http_build_query($queryParams);
        }

        return $dsn;
    }

    private function getTlsQueryParams(string $tlsMode): array
    {
        return match ($tlsMode) {
            'tls' => ['cacert' => self::CACERT_PATH],
            'mtls' => [
                'cacert' => self::CACERT_PATH,
                'cert' => self::CLIENT_CERT_PATH,
                'key' => self::CLIENT_KEY_PATH,
            ],
            default => [],
        };
    }
}
