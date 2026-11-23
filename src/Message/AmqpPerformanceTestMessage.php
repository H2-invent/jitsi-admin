<?php

namespace App\Message;

final class AmqpPerformanceTestMessage
{
    public function __construct(
        public readonly string $runId,
        public readonly int $sequence,
        public readonly float $sentAt,
        public readonly string $payload,
    )
    {
    }
}
