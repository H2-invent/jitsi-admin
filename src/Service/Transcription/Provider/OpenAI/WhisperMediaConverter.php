<?php
declare(strict_types=1);

namespace App\Service\Transcription\Provider\OpenAI;

use App\Service\Transcription\Provider\AbstractMediaConverter;
use FFMpeg\Format\Audio\DefaultAudio;
use FFMpeg\Format\Audio\Mp3;
use Generator;

class WhisperMediaConverter extends AbstractMediaConverter
{
    private const MAX_CHUNK_BYTES = 24 * 1024 * 1024; // OpenAI max chunk size is 25MB, we're doing 24 here for safety
    private const MP3_KBIT = 128; // Quality pretty bad to optimize for file size

    protected function createAudioFormat(): DefaultAudio
    {
        return (new Mp3())->setAudioChannels(1)->setAudioKiloBitrate(self::MP3_KBIT);
    }

    protected function splitAudioIntoChunks(string $mp3FilePath): Generator
    {
        return $this->splitByFileSize($mp3FilePath, self::MAX_CHUNK_BYTES);
    }
}
