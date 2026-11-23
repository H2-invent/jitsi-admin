<?php

namespace App\Tests\Monolog\Handler;

use App\Monolog\Handler\ExcludeHealthCheckHandler;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

class ExcludeHealthCheckHandlerTest extends TestCase
{
    private function record(array $context = [], array $extra = []): LogRecord
    {
        return new LogRecord(new \DateTimeImmutable(), 'app', Level::Info, 'message', $context, $extra);
    }

    public function testHealthCheckRouteInContextIsNotHandled(): void
    {
        $handler = new ExcludeHealthCheckHandler(new TestHandler());

        $this->assertFalse($handler->isHandling($this->record(['route' => 'health_check'])));
    }

    public function testHealthCheckRouteInExtraIsNotHandled(): void
    {
        $handler = new ExcludeHealthCheckHandler(new TestHandler());

        $this->assertFalse($handler->isHandling($this->record([], ['route' => 'health_check'])));
    }

    public function testOtherRouteIsHandled(): void
    {
        $handler = new ExcludeHealthCheckHandler(new TestHandler());

        $this->assertTrue($handler->isHandling($this->record(['route' => 'dashboard'])));
    }

    public function testRecordWithoutRouteIsHandled(): void
    {
        $handler = new ExcludeHealthCheckHandler(new TestHandler());

        $this->assertTrue($handler->isHandling($this->record()));
    }
}
