<?php

namespace App\Tests\Message;

use App\Message\LobbyLeaverMessage;
use PHPUnit\Framework\TestCase;

class LobbyLeaverMessageTest extends TestCase
{
    public function testConstructorExposesId(): void
    {
        $message = new LobbyLeaverMessage('lobby-uid');

        $this->assertSame('lobby-uid', $message->getId());
    }

    public function testSetId(): void
    {
        $message = new LobbyLeaverMessage('first');
        $message->setId('second');

        $this->assertSame('second', $message->getId());
    }
}
