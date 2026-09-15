<?php
declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\TranscriptionMessage;
use App\Repository\UploadedRecordingRepository;
use App\Service\Transcription\TranscriptionService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class TranscriptionMessageHandler
{
    public function __construct(
        private readonly TranscriptionService $service,
        private readonly UploadedRecordingRepository $uploadedRecordingRepository,
    )
    {
    }

    public function __invoke(TranscriptionMessage $message): void
    {
        $uploadedRecording = $this->uploadedRecordingRepository->findOneBy(['id' => $message->getUploadedRecordingId()]);
        $this->service->transcribe($uploadedRecording);
    }
}
