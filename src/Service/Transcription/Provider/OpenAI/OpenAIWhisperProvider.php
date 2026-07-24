<?php
declare(strict_types=1);

namespace App\Service\Transcription\Provider\OpenAI;

use App\Entity\Server;
use App\Service\Transcription\TranscriptionProviderInterface;
use Generator;

class OpenAIWhisperProvider implements TranscriptionProviderInterface
{
    public function __construct(
        private readonly WhisperMediaConverter $converter,
        private readonly WhisperTranscriber $transcriber,
    )
    {
    }

    public function yieldAudioChunks(string $recordingFileKey): Generator
    {
        return $this->converter->yieldMp3ChunksOfRecording($recordingFileKey);
    }

    /**
     * @inheritDoc
     */
    public function transcribeChunks(Generator $audioChunksGenerator, Server $server): array
    {
        return $this->transcriber->transcribeAudioChunks($audioChunksGenerator, $server);
    }

    /**
     * @param string[] $chunkPaths
     */
    public function deleteChunks(array $chunkPaths): void
    {
        $this->converter->deleteChunks($chunkPaths);
    }
}
