<?php
declare(strict_types=1);

namespace App\Service\Transcription\Provider;

use App\Entity\Server;
use Generator;

abstract class AbstractTranscriptionProvider
{
    public function __construct(
        protected readonly AbstractMediaConverter $converter,
        protected readonly AbstractTranscriber $transcriber,
    ) {
    }

    public function yieldAudioChunks(string $recordingFileKey): Generator
    {
        return $this->converter->yieldChunksOfRecording($recordingFileKey);
    }

    /**
     * Returns the combined text, and the paths to the chunks for deletion.
     * @return array{0: string, 1: array<string>}
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

