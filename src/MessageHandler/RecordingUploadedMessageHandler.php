<?php
declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\RecordingUploadedMessage;
use App\Service\RecordingService;
use App\Service\Result\Error\RecordingFinalizeError;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
class RecordingUploadedMessageHandler
{
    public function __construct(
        private readonly RecordingService $service,
    )
    {
    }

    public function __invoke(RecordingUploadedMessage $message): void
    {
        $result = $this->service->finalizeUpload($message->getRecordingId());

        if ($result->isFailure()) {
            switch ($result->getErrorType()) {
                case RecordingFinalizeError::NO_CHUNKS_FOUND:
                case RecordingFinalizeError::NO_RECORDING_FOUND:
                    throw new UnrecoverableMessageHandlingException($result->getErrorType()->value);
            }
        }
    }
}
