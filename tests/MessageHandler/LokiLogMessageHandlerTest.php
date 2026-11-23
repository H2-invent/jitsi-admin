<?php

namespace App\Tests\MessageHandler;

use App\Message\LokiLogMessage;
use App\MessageHandler\LokiLogMessageHandler;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

class LokiLogMessageHandlerTest extends TestCase
{
    public function testInvokeForwardsAllRecordsToTheLokiHandler(): void
    {
        $lokiHandler = new TestHandler();
        $handler = new LokiLogMessageHandler($lokiHandler);

        $records = [
            new LogRecord(new \DateTimeImmutable(), 'app', Level::Info, 'one'),
            new LogRecord(new \DateTimeImmutable(), 'app', Level::Warning, 'two'),
        ];

        $handler(new LokiLogMessage($records));

        $this->assertCount(2, $lokiHandler->getRecords());
    }

    public function testInvokeWithNoRecordsDoesNothing(): void
    {
        $lokiHandler = new TestHandler();
        $handler = new LokiLogMessageHandler($lokiHandler);

        $handler(new LokiLogMessage([]));

        $this->assertCount(0, $lokiHandler->getRecords());
    }
}
