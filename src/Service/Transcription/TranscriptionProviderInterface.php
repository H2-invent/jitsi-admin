<?php
declare(strict_types=1);

namespace App\Service\Transcription;

use App\Entity\Server;
use Generator;

interface TranscriptionProviderInterface
{
    public function yieldAudioChunks(string $recordingFileKey): Generator;

    /**
     * @return array{0: string, 1: array<string>}
     */
    public function transcribeChunks(Generator $audioChunksGenerator, Server $server): array;

    /**
     * @param string[] $chunkPaths
     */
    public function deleteChunks(array $chunkPaths): void;
}
