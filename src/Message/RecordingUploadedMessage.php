<?php
declare(strict_types=1);

namespace App\Message;

readonly class RecordingUploadedMessage
{
    public function __construct(
        private string $recordingId,
    )
    {
    }

    public function getRecordingId(): string
    {
        return $this->recordingId;
    }
}
