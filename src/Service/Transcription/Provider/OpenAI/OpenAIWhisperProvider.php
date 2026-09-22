<?php
declare(strict_types=1);

namespace App\Service\Transcription\Provider\OpenAI;

use App\Service\Transcription\Provider\AbstractTranscriptionProvider;

class OpenAIWhisperProvider extends AbstractTranscriptionProvider
{
    public function __construct(
        WhisperMediaConverter $converter,
        WhisperTranscriber $transcriber,
    ) {
        parent::__construct($converter, $transcriber);
    }
}
