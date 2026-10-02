<?php

namespace App\Tests\Clock;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * Test clock that freezes time for the whole PHP process.
 *
 * The test client reboots the kernel between requests, so a per-container clock
 * would report a different "now" for every request. Freezing time process-wide
 * keeps generated JWTs deterministic across kernel reboots while staying close
 * to the real current time (so decoded tokens are neither already expired nor
 * "not valid yet").
 */
final class FixedClock implements ClockInterface
{
    private static ?DateTimeImmutable $now = null;

    public function now(): DateTimeImmutable
    {
        return self::$now ??= new DateTimeImmutable();
    }
}
