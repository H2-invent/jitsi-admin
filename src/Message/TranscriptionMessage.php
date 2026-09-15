<?php
declare(strict_types=1);

namespace App\Message;

readonly class TranscriptionMessage
{
    public function __construct(
        private int $uploadedRecordingId,
    )
    {
    }

    public function getUploadedRecordingId(): int
    {
        return $this->uploadedRecordingId;
    }
}
