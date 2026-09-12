<?php

namespace App\Tests\Message;

use App\Message\TranscriptionMessage;
use PHPUnit\Framework\TestCase;

class TranscriptionMessageTest extends TestCase
{
    public function testGetUploadedRecordingIdReturnsTheGivenId(): void
    {
        $message = new TranscriptionMessage(42);

        $this->assertSame(42, $message->getUploadedRecordingId());
    }
}
