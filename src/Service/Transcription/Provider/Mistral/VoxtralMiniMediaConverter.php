<?php
declare(strict_types=1);

namespace App\Service\Transcription\Provider\Mistral;

use App\Service\Transcription\Provider\AbstractMediaConverter;
use FFMpeg\Format\Audio\DefaultAudio;
use FFMpeg\Format\Audio\Mp3;
use Generator;

class VoxtralMiniMediaConverter extends AbstractMediaConverter
{
    private const MAX_CHUNK_SECONDS = 60 * 60 * 3; // Mistral AI accepts around 3 hours of audio
    private const MP3_KBIT = 192; // Quality pretty good, file size is no big concern

    protected function createAudioFormat(): DefaultAudio
    {
        return (new Mp3())->setAudioChannels(1)->setAudioKiloBitrate(self::MP3_KBIT);
    }

    protected function splitAudioIntoChunks(string $mp3FilePath): Generator
    {
        return $this->splitByDuration($mp3FilePath, self::MAX_CHUNK_SECONDS);
    }
}
