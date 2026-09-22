<?php
declare(strict_types=1);

namespace App\Service\Transcription;

use App\Entity\Server;
use App\Service\Transcription\Provider\AbstractTranscriptionProvider;
use App\Service\Transcription\Provider\Mistral\MistralVoxtralMiniProvider;
use App\Service\Transcription\Provider\OpenAI\OpenAIWhisperProvider;
use RuntimeException;

class TranscriptionProviderResolver
{
    public function __construct(
        private readonly OpenAIWhisperProvider $whisperProvider,
        private readonly MistralVoxtralMiniProvider $voxtralMiniProvider,
    )
    {
    }

    public function resolve(Server $server): AbstractTranscriptionProvider
    {
        return match($server->getTranscriptionProvider()) {
            TranscriptionProvider::OPEN_AI_WHISPER => $this->whisperProvider,
            TranscriptionProvider::MISTRAL_VOXTRAL_MINI => $this->voxtralMiniProvider,
            default => throw new RuntimeException("Unsupported Transcription Provider in Settings")
        };
    }
}
