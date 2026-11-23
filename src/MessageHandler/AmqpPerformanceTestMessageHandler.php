<?php

namespace App\MessageHandler;

use App\Message\AmqpPerformanceTestMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Performance test messages are consumed by app:amqp:performance-test itself.
 * This handler only exists so a worker consuming the "test" transport discards leftovers instead of failing.
 */
#[AsMessageHandler]
final class AmqpPerformanceTestMessageHandler
{
    public function __invoke(AmqpPerformanceTestMessage $message): void
    {
    }
}
