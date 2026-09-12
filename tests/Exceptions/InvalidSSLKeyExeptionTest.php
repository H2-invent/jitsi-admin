<?php

namespace App\Tests\Exceptions;

use App\Exceptions\InvalidSSLKeyExeption;
use PHPUnit\Framework\TestCase;

class InvalidSSLKeyExeptionTest extends TestCase
{
    public function testIsAnExceptionAndThrowable(): void
    {
        $exception = new InvalidSSLKeyExeption();

        self::assertInstanceOf(\Throwable::class, $exception);
        self::assertInstanceOf(\Exception::class, $exception);
    }

    public function testCarriesFixedMessageAndCode(): void
    {
        $exception = new InvalidSSLKeyExeption();

        self::assertSame('Invalid SSL key fethced from Livekit Server', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
    }

    public function testCanBeThrownAndCaught(): void
    {
        $caught = null;

        try {
            throw new InvalidSSLKeyExeption();
        } catch (InvalidSSLKeyExeption $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(InvalidSSLKeyExeption::class, $caught);
        self::assertSame('Invalid SSL key fethced from Livekit Server', $caught->getMessage());
    }

    public function testCustomMessageReturnsNull(): void
    {
        self::assertNull((new InvalidSSLKeyExeption())->customMessage());
    }
}
