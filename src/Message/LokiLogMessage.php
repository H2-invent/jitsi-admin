<?php
declare(strict_types=1);

namespace App\Message;

use Monolog\LogRecord;

readonly class LokiLogMessage
{
    public function __construct(
        /** @var LogRecord[] $records */
        private array $records,
    )
    {
    }

    /**
     * @return LogRecord[]
     */
    public function getRecords(): array
    {
        return $this->records;
    }
}
