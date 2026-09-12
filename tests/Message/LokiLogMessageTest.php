<?php

namespace App\Tests\Message;

use App\Message\LokiLogMessage;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

class LokiLogMessageTest extends TestCase
{
    public function testGetRecordsReturnsTheGivenRecords(): void
    {
        $records = [
            new LogRecord(new \DateTimeImmutable(), 'app', Level::Info, 'one'),
            new LogRecord(new \DateTimeImmutable(), 'app', Level::Warning, 'two'),
        ];

        $message = new LokiLogMessage($records);

        $this->assertSame($records, $message->getRecords());
    }

    public function testGetRecordsCanBeEmpty(): void
    {
        $message = new LokiLogMessage([]);

        $this->assertSame([], $message->getRecords());
    }
}
