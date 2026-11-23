<?php

namespace App\Tests\Monolog\Handler;

use App\Message\LokiLogMessage;
use App\Monolog\Handler\AsyncLokiHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

class AsyncLokiHandlerTest extends KernelTestCase
{
    private function createHandler(): AsyncLokiHandler
    {
        self::bootKernel();

        return new AsyncLokiHandler(self::getContainer()->get(MessageBusInterface::class));
    }

    private function transport(): InMemoryTransport
    {
        return self::getContainer()->get('messenger.transport.async');
    }

    private function record(string $message): LogRecord
    {
        return new LogRecord(new \DateTimeImmutable(), 'app', Level::Info, $message);
    }

    public function testHandleBatchDispatchesLokiLogMessage(): void
    {
        $handler = $this->createHandler();
        $records = [$this->record('one'), $this->record('two')];

        $handler->handleBatch($records);

        $sent = $this->transport()->getSent();
        $this->assertCount(1, $sent);
        $message = $sent[0]->getMessage();
        $this->assertInstanceOf(LokiLogMessage::class, $message);
        $this->assertSame($records, $message->getRecords());
    }

    public function testHandleDispatchesSingleRecordAndReturnsTrue(): void
    {
        $handler = $this->createHandler();
        $record = $this->record('single');

        $this->assertTrue($handler->handle($record));

        $sent = $this->transport()->getSent();
        $this->assertCount(1, $sent);
        $this->assertSame([$record], $sent[0]->getMessage()->getRecords());
    }

    public function testIsHandlingAlwaysReturnsTrue(): void
    {
        $handler = $this->createHandler();

        $this->assertTrue($handler->isHandling($this->record('any')));
    }
}
