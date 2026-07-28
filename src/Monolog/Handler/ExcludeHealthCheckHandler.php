<?php
declare(strict_types=1);

namespace App\Monolog\Handler;

use Monolog\Handler\FilterHandler;
use Monolog\LogRecord;

class ExcludeHealthCheckHandler extends FilterHandler
{
    private const EXCLUDED_ROUTE = 'health_check';

    public function isHandling(LogRecord $record): bool
    {
        $route = $record->context['route'] ?? $record->extra['route'] ?? '';
        if ($route === self::EXCLUDED_ROUTE) {
            return false;
        }

        return parent::isHandling($record);
    }
}
