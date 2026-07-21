<?php
declare(strict_types=1);

namespace App\Service\Transcription;

use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Audio\Mp3;
use Gaufrette\FilesystemInterface;
use Generator;
use RuntimeException;

class MediaConverter
{
    private const MAX_CHUNK_BYTES = 24 * 1024 * 1024; // OpenAI max chunk size is 25MB, we're doing 24 here for safety
    private const AUDIO_KILO_BITRATE = 128;

    private readonly Mp3 $lowQualityMp3;

    public function __construct(
        private readonly FFMpeg $ffmpeg,
        private readonly FilesystemInterface $recordingFilesystem,
    )
    {
        $this->lowQualityMp3 = (new Mp3())->setAudioChannels(1)->setAudioKiloBitrate(self::AUDIO_KILO_BITRATE);
    }

    public function yieldMp3ChunksOfRecording(string $recordingFileKey): Generator
    {
        $mp3Path = $this->convertRecordingToMp3($recordingFileKey);

        yield from $this->splitMp3IntoChunks($mp3Path);
    }

    /**
     * @param list<string> $chunkPaths
     */
    public function deleteChunks(array $chunkPaths): void
    {
        foreach ($chunkPaths as $chunkPath) {
            if (file_exists($chunkPath)) {
                unlink($chunkPath);
            }
        }
    }

    private function convertRecordingToMp3(string $recordingFileKey): string
    {
        if (!$this->recordingFilesystem->has($recordingFileKey)) {
            throw new RuntimeException("Could not find {$recordingFileKey} on the recording filesystem");
        }

        $recordingFile = $this->recordingFilesystem->get($recordingFileKey);

        $tempVideoPath = sys_get_temp_dir() . '/' . uniqid('recording_video', true) . '.mp4';
        $tempAudioPath = sys_get_temp_dir() . '/' . uniqid('recording_audio', true) . '.mp3';

        try {
            file_put_contents($tempVideoPath, $recordingFile->getContent());

            $video = $this->ffmpeg->open($tempVideoPath);
            $video->save($this->lowQualityMp3, $tempAudioPath);

            return $tempAudioPath;

        } finally {
            if (file_exists($tempVideoPath)) {
                unlink($tempVideoPath);
            }
        }
    }

    private function splitMp3IntoChunks(string $mp3FilePath): Generator
    {
        $audio = $this->ffmpeg->open($mp3FilePath);
        $duration = $audio->getFormat()->get('duration'); // duration in seconds
        $fileSize = filesize($mp3FilePath);

        if ($fileSize <= self::MAX_CHUNK_BYTES) {
            // no splitting needed
            yield $mp3FilePath;
            return;
        }

        // calculate approximate chunk duration based on bitrate
        $bytesPerSecond = $fileSize / $duration;
        $chunkDuration = floor(self::MAX_CHUNK_BYTES / $bytesPerSecond);

        $startTime = 0;
        while ($startTime < $duration) {
            $chunkPath = sys_get_temp_dir() . '/' . uniqid("audio_chunk_", true) . '.mp3';

            $audio = $this->ffmpeg->open($mp3FilePath);
            $audio->filters()->clip(
                TimeCode::fromSeconds($startTime),
                TimeCode::fromSeconds(min($chunkDuration, $duration - $startTime))
            );
            $audio->save($this->lowQualityMp3, $chunkPath);

            $startTime += $chunkDuration;

            yield $chunkPath;
        }
    }
}
