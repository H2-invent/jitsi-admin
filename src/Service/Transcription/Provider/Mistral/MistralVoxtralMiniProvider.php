<?php
declare(strict_types=1);

namespace App\Service\Transcription\Provider\Mistral;

use App\Service\Transcription\Provider\AbstractTranscriptionProvider;

class MistralVoxtralMiniProvider extends AbstractTranscriptionProvider
{
    public function __construct(
        VoxtralMiniMediaConverter $converter,
        VoxtralMiniTranscriber $transcriber,
    ) {
        parent::__construct($converter, $transcriber);
    }
}
