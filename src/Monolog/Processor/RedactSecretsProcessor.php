<?php
declare(strict_types=1);

namespace App\Monolog\Processor;

use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Redacts both JWTs and Secrets from URL strings
 */
#[AsMonologProcessor]
class RedactSecretsProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $extra = $record->extra;
        $context = $record->context;

        foreach (['request_uri', 'uri', 'url'] as $field) {
            if (isset($extra[$field])) {
                $extra[$field] = $this->sanitizeUrl($extra[$field]);
            }
        }
        foreach (['request_uri', 'uri', 'url'] as $field) {
            if (isset($context[$field])) {
                $context[$field] = $this->sanitizeUrl($context[$field]);
            }
        }

        return $record->with(
            context: $context,
            extra: $extra,
        );
    }

    private function sanitizeUrl(string $value): string
    {
        $value = rawurldecode($value); // we may get url encoded string here, so decode it first to match all base64 chars

        $value = preg_replace('~((?:jwt|token|auth|access_token)=)[A-Za-z0-9_-]{2,}(?:\.[A-Za-z0-9_-]{2,}){2}~', '$1[JWT_REDACTED]', $value);
        $value = preg_replace('~(secret=)(?:[A-Za-z0-9+/]{4})*(?:[A-Za-z0-9+/]{2}==|[A-Za-z0-9+/]{3}=)?~', '$1[SECRET_REDACTED]', $value);

        return $value;
    }
}
