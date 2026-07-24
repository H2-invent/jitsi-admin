<?php
declare(strict_types=1);

namespace App\Service\Transcription;

use App\Entity\Server;
use App\Service\Transcription\Provider\OpenAI\OpenAIWhisperProvider;

class TranscriptionProviderResolver
{
    public function __construct(
        private readonly OpenAIWhisperProvider $whisperProvider,
    )
    {
    }

    public function resolve(Server $server): TranscriptionProviderInterface
    {
//        return match($server->get)
    }
}
