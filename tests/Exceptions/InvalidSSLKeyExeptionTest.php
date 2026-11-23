<?php

namespace App\Tests\Exceptions;

use App\Exceptions\InvalidSSLKeyExeption;
use PHPUnit\Framework\TestCase;

class InvalidSSLKeyExeptionTest extends TestCase
{
    public function testCarriesFixedMessageAndCode(): void
    {
        $exception = new InvalidSSLKeyExeption();

        self::assertSame('Invalid SSL key fethced from Livekit Server', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
    }
}
