<?php
declare(strict_types=1);

namespace App\Monolog\Handler;

use App\Message\LokiLogMessage;
use Monolog\Handler\Handler;
use Monolog\LogRecord;
use Symfony\Component\Messenger\MessageBusInterface;

class AsyncLokiHandler extends Handler
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    )
    {
    }

    public function handleBatch(array $records): void
    {
        $this->messageBus->dispatch(
            new LokiLogMessage($records),
        );
    }

    public function handle(LogRecord $record): bool
    {
        $this->handleBatch([$record]);

        return true;
    }

    public function isHandling(LogRecord $record): bool
    {
        // handling should have been filtered prior to this, we'll just handle all thrown at this
        return true;
    }
}
