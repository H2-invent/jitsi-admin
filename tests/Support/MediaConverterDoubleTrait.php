<?php
declare(strict_types=1);

namespace App\Tests\Support;

use FFMpeg\Format\Audio\DefaultAudio;
use FFMpeg\Format\Audio\Mp3;
use Generator;

trait MediaConverterDoubleTrait
{
    /** @var array<string> */
    public array $chunks = [];

    public ?string $requestedKey = null;

    /** @var array<array<string>> */
    public array $deleted = [];

    protected function createAudioFormat(): DefaultAudio
    {
        return new Mp3();
    }

    public function yieldChunksOfRecording(string $recordingFileKey): Generator
    {
        $this->requestedKey = $recordingFileKey;
        yield from $this->chunks;
    }

    public function deleteChunks(array $chunkPaths): void
    {
        $this->deleted[] = $chunkPaths;
    }
}
