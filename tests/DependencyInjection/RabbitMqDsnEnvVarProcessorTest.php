<?php
declare(strict_types=1);

namespace App\Tests\DependencyInjection;

use App\DependencyInjection\RabbitMqDsnEnvVarProcessor;
use Closure;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\IncompleteTestError;
use PHPUnit\Framework\TestCase;

class RabbitMqDsnEnvVarProcessorTest extends TestCase
{
    private const BASE_DSN = 'amqp://user:pass@localhost:5672/%2f/messages';

    public function testNoTls(): void
    {
        $processor = new RabbitMqDsnEnvVarProcessor();
        $dsn = $processor->getEnv('rabbitmq_dsn', 'RABBITMQ_DSN', $this->createGetEnv('none'));

        self::assertStringStartsWith('amqp://', $dsn);
        self::assertDoesNotMatchRegularExpression('~[?&]cacert=~', $dsn);
        self::assertDoesNotMatchRegularExpression('~[?&]cert=~', $dsn);
        self::assertDoesNotMatchRegularExpression('~[?&]key=~', $dsn);
    }

    public function testTls(): void
    {
        $processor = new RabbitMqDsnEnvVarProcessor();
        $dsn = $processor->getEnv('rabbitmq_dsn', 'RABBITMQ_DSN', $this->createGetEnv('tls'));

        self::assertStringStartsWith('amqps://', $dsn);
        self::assertMatchesRegularExpression('~[?&]cacert=~', $dsn);
        self::assertDoesNotMatchRegularExpression('~[?&]cert=~', $dsn);
        self::assertDoesNotMatchRegularExpression('~[?&]key=~', $dsn);
    }

    public function testMtls(): void
    {
        $processor = new RabbitMqDsnEnvVarProcessor();
        $dsn = $processor->getEnv('rabbitmq_dsn', 'RABBITMQ_DSN', $this->createGetEnv('mtls'));

        self::assertStringStartsWith('amqps://', $dsn);
        self::assertMatchesRegularExpression('~[?&]cacert=~', $dsn);
        self::assertMatchesRegularExpression('~[?&]cert=~', $dsn);
        self::assertMatchesRegularExpression('~[?&]key=~', $dsn);
    }

    public function testInvalidTlsModeThrows(): void
    {
        $processor = new RabbitMqDsnEnvVarProcessor();

        $this->expectException(InvalidArgumentException::class);

        $processor->getEnv('rabbitmq_dsn', 'RABBITMQ_DSN', $this->createGetEnv('invalid'));
    }

    public function testInvalidDsnThrows(): void
    {
        $processor = new RabbitMqDsnEnvVarProcessor();

        $this->expectException(InvalidArgumentException::class);

        $processor->getEnv('rabbitmq_dsn', 'RABBITMQ_DSN', $this->createGetEnv('none', 'not-a-valid-dsn'));
    }

    public function testProvidedTypes(): void
    {
        self::assertSame(['rabbitmq_dsn' => 'string'], RabbitMqDsnEnvVarProcessor::getProvidedTypes());
    }

    private function createGetEnv(string $tlsMode, string $baseDsn = self::BASE_DSN): Closure
    {
        return static fn(string $name) => match ($name) {
            'RABBITMQ_DSN' => $baseDsn,
            'RABBITMQ_TLS_MODE' => $tlsMode,
            default => throw new IncompleteTestError("Unknown env var: $name"),
        };
    }
}


