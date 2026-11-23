<?php

namespace App\Tests\Monolog\Processor;

use App\Monolog\Processor\RedactSecretsProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

class RedactSecretsProcessorTest extends TestCase
{
    private function record(array $context = [], array $extra = []): LogRecord
    {
        return new LogRecord(new \DateTimeImmutable(), 'app', Level::Info, 'message', $context, $extra);
    }

    public function testRedactsJwtAndSecretInContext(): void
    {
        $processor = new RedactSecretsProcessor();

        $result = $processor($this->record([
            'request_uri' => 'https://example.com/room?jwt=aaa.bbb.ccc&foo=bar',
            'uri' => 'https://example.com/room?secret=YWJjZA==',
            'other' => 'leave-me',
        ]));

        $this->assertSame('https://example.com/room?jwt=[JWT_REDACTED]&foo=bar', $result->context['request_uri']);
        $this->assertSame('https://example.com/room?secret=[SECRET_REDACTED]', $result->context['uri']);
        $this->assertSame('leave-me', $result->context['other']);
    }

    public function testRedactsJwtAndSecretInExtra(): void
    {
        $processor = new RedactSecretsProcessor();

        $result = $processor($this->record([], [
            'url' => 'https://example.com?access_token=ddd.eee.fff',
            'nothing' => 'keep',
        ]));

        $this->assertSame('https://example.com?access_token=[JWT_REDACTED]', $result->extra['url']);
        $this->assertSame('keep', $result->extra['nothing']);
    }

    public function testDecodesUrlEncodedValuesBeforeRedacting(): void
    {
        $processor = new RedactSecretsProcessor();

        $result = $processor($this->record(['request_uri' => 'https://example.com?jwt%3Daaa.bbb.ccc']));

        $this->assertSame('https://example.com?jwt=[JWT_REDACTED]', $result->context['request_uri']);
    }

    public function testLeavesRecordUntouchedWhenNoSensitiveFieldIsPresent(): void
    {
        $processor = new RedactSecretsProcessor();

        $result = $processor($this->record(['request_uri' => 'https://example.com/plain'], ['url' => 'https://example.com/plain']));

        $this->assertSame('https://example.com/plain', $result->context['request_uri']);
        $this->assertSame('https://example.com/plain', $result->extra['url']);
    }
}
