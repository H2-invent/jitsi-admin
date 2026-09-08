<?php
declare(strict_types=1);

namespace App\Service\Transcription\Provider;

use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Audio\DefaultAudio;
use Gaufrette\FilesystemInterface;
use Generator;
use RuntimeException;

abstract class AbstractMediaConverter
{
    protected readonly DefaultAudio $audioFormat;

    public function __construct(
        protected readonly FFMpeg $ffmpeg,
        protected readonly FilesystemInterface $recordingFilesystem,
    ) {
        $this->audioFormat = $this->createAudioFormat();
    }

    abstract protected function createAudioFormat(): DefaultAudio;

    abstract protected function splitAudioIntoChunks(string $mp3FilePath): Generator;

    public function yieldChunksOfRecording(string $recordingFileKey): Generator
    {
        $mp3Path = $this->convertRecordingToMp3($recordingFileKey);
        yield from $this->splitAudioIntoChunks($mp3Path);
    }

    public function deleteChunks(array $chunkPaths): void
    {
        foreach ($chunkPaths as $chunkPath) {
            if (file_exists($chunkPath)) {
                unlink($chunkPath);
            }
        }
    }

    protected function convertRecordingToMp3(string $recordingFileKey): string
    {
        $filesystem = $this->recordingFilesystem;

        if (!$filesystem->has($recordingFileKey)) {
            throw new RuntimeException("Could not find {$recordingFileKey} on the recording filesystem");
        }

        $recordingFile = $filesystem->get($recordingFileKey);
        $tempVideoPath = sys_get_temp_dir() . '/' . uniqid('recording_video', true) . '.mp4';
        $tempAudioPath = sys_get_temp_dir() . '/' . uniqid('recording_audio', true) . '.mp3';

        try {
            file_put_contents($tempVideoPath, $recordingFile->getContent());
            $video = $this->ffmpeg->open($tempVideoPath);
            $video->save($this->audioFormat, $tempAudioPath);

            return $tempAudioPath;
        } finally {
            if (file_exists($tempVideoPath)) {
                unlink($tempVideoPath);
            }
        }
    }

    /**
     * Helper for time-based chunking (used by multiple providers)
     */
    protected function splitByDuration(string $mp3FilePath, float $maxChunkSeconds): Generator
    {
        $audio = $this->ffmpeg->open($mp3FilePath);
        $duration = $audio->getFormat()->get('duration');

        if ($duration <= $maxChunkSeconds) {
            yield $mp3FilePath;

            return;
        }

        $startTime = 0;
        while ($startTime < $duration) {
            $chunkPath = sys_get_temp_dir() . '/' . uniqid("audio_chunk_", true) . '.mp3';
            $audio = $this->ffmpeg->open($mp3FilePath);
            $audio->filters()->clip(
                TimeCode::fromSeconds($startTime),
                TimeCode::fromSeconds(min($maxChunkSeconds, $duration - $startTime))
            );
            $audio->save($this->audioFormat, $chunkPath);
            $startTime += $maxChunkSeconds;
            yield $chunkPath;
        }
    }

    /**
     * Helper for size-based chunking
     */
    protected function splitByFileSize(string $mp3FilePath, int $maxChunkBytes): Generator
    {
        $audio = $this->ffmpeg->open($mp3FilePath);
        $duration = $audio->getFormat()->get('duration');
        $fileSize = filesize($mp3FilePath);

        if ($fileSize <= $maxChunkBytes) {
            yield $mp3FilePath;

            return;
        }

        $bytesPerSecond = $fileSize / $duration;
        $chunkDuration = floor($maxChunkBytes / $bytesPerSecond);

        yield from $this->splitByDuration($mp3FilePath, $chunkDuration);
    }
}

