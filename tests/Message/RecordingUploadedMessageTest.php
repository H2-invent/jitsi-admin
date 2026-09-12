<?php

namespace App\Tests\Message;

use App\Message\RecordingUploadedMessage;
use PHPUnit\Framework\TestCase;

class RecordingUploadedMessageTest extends TestCase
{
    public function testGetRecordingIdReturnsTheGivenId(): void
    {
        $message = new RecordingUploadedMessage('recording-123');

        $this->assertSame('recording-123', $message->getRecordingId());
    }
}
