<?php
declare(strict_types=1);

namespace App\Service\Transcription;

enum TranscriptionProvider: string
{
    case OPEN_AI_WHISPER = 'Open AI: Whisper-1';
    case MISTRAL_VOXTRAL_MINI = 'Mistral: Voxtral Mini Transcribe 2';
}
