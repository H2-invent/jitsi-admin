<?php
declare(strict_types=1);

namespace App\Tests\Service\Transcription\Provider;

use App\Service\Transcription\Provider\AbstractMediaConverter;
use FFMpeg\Driver\FFMpegDriver;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Format\Audio\DefaultAudio;
use FFMpeg\Format\Audio\Mp3;
use FFMpeg\Media\Video;
use Gaufrette\File;
use Gaufrette\FilesystemInterface;
use Generator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AbstractMediaConverterTest extends TestCase
{
    public function testConstructorCallsCreateAudioFormat(): void
    {
        $converter = new AbstractMediaConverterTestDouble(
            $this->createMock(FFMpeg::class),
            $this->createMock(FilesystemInterface::class),
        );

        $format = $this->invokeProtected($converter, 'createAudioFormat');

        $this->assertInstanceOf(Mp3::class, $format);
    }

    public function testConvertRecordingToMp3ThrowsWhenRecordingMissing(): void
    {
        $filesystem = $this->createMock(FilesystemInterface::class);
        $filesystem->method('has')->with('missing.mp4')->willReturn(false);

        $converter = new AbstractMediaConverterTestDouble($this->createMock(FFMpeg::class), $filesystem);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not find missing.mp4 on the recording filesystem');

        $converter->callConvertRecordingToMp3('missing.mp4');
    }

    public function testConvertRecordingToMp3EncodesRecordingAndRemovesTempVideo(): void
    {
        $file = $this->createMock(File::class);
        $file->method('getContent')->willReturn('raw-video-bytes');

        $filesystem = $this->createMock(FilesystemInterface::class);
        $filesystem->method('has')->with('recording.mp4')->willReturn(true);
        $filesystem->method('get')->with('recording.mp4')->willReturn($file);

        $capturedVideoPath = null;
        $capturedVideoContent = null;
        $savedFormat = null;
        $savedAudioPath = null;

        $video = $this->getMockBuilder(Video::class)
            ->setConstructorArgs(['/tmp/unused.mp4', $this->createMock(FFMpegDriver::class), $this->createMock(FFProbe::class)])
            ->onlyMethods(['save'])
            ->getMock();
        $video->method('save')->willReturnCallback(
            function ($format, string $path) use (&$savedFormat, &$savedAudioPath, $video) {
                $savedFormat = $format;
                $savedAudioPath = $path;
                file_put_contents($path, 'encoded-audio');
                return $video;
            }
        );

        $ffmpeg = $this->createMock(FFMpeg::class);
        $ffmpeg->method('open')->willReturnCallback(
            function (string $path) use (&$capturedVideoPath, &$capturedVideoContent, $video) {
                $capturedVideoPath = $path;
                $capturedVideoContent = file_get_contents($path);
                return $video;
            }
        );

        $converter = new AbstractMediaConverterTestDouble($ffmpeg, $filesystem);

        try {
            $audioPath = $converter->callConvertRecordingToMp3('recording.mp4');

            $this->assertSame('raw-video-bytes', $capturedVideoContent);
            $this->assertNotNull($capturedVideoPath);
            $this->assertStringEndsWith('.mp4', $capturedVideoPath);
            $this->assertFileDoesNotExist($capturedVideoPath);

            $this->assertSame($audioPath, $savedAudioPath);
            $this->assertStringEndsWith('.mp3', $audioPath);
            $this->assertFileExists($audioPath);
            $this->assertSame('encoded-audio', file_get_contents($audioPath));
            $this->assertInstanceOf(DefaultAudio::class, $savedFormat);
        } finally {
            if ($savedAudioPath !== null && file_exists($savedAudioPath)) {
                unlink($savedAudioPath);
            }
        }
    }

    public function testYieldChunksOfRecordingConvertsThenDelegatesToSplit(): void
    {
        $converter = new AbstractMediaConverterTestYieldDouble(
            $this->createMock(FFMpeg::class),
            $this->createMock(FilesystemInterface::class),
        );

        $chunks = iterator_to_array($converter->yieldChunksOfRecording('recording.mp4'));

        $this->assertSame(['/converted/recording.mp4.mp3'], $converter->splitInput);
        $this->assertSame(['chunk-a', 'chunk-b'], $chunks);
    }

    public function testDeleteChunksRemovesExistingFilesAndIgnoresMissingOnes(): void
    {
        $converter = new AbstractMediaConverterTestDouble(
            $this->createMock(FFMpeg::class),
            $this->createMock(FilesystemInterface::class),
        );

        $first = tempnam(sys_get_temp_dir(), 'chunk_first_');
        $second = tempnam(sys_get_temp_dir(), 'chunk_second_');
        file_put_contents($first, 'a');
        file_put_contents($second, 'b');

        $converter->deleteChunks([$first, $second, '/definitely/not/here-' . uniqid() . '.mp3']);

        $this->assertFileDoesNotExist($first);
        $this->assertFileDoesNotExist($second);
    }

    private function invokeProtected(object $object, string $method, array $arguments = []): mixed
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $arguments);
    }
}

class AbstractMediaConverterTestDouble extends AbstractMediaConverter
{
    protected function createAudioFormat(): DefaultAudio
    {
        return (new Mp3())->setAudioChannels(1);
    }

    protected function splitAudioIntoChunks(string $mp3FilePath): Generator
    {
        yield from $this->splitByDuration($mp3FilePath, 10.0);
    }

    public function callConvertRecordingToMp3(string $recordingFileKey): string
    {
        return $this->convertRecordingToMp3($recordingFileKey);
    }
}

class AbstractMediaConverterTestYieldDouble extends AbstractMediaConverter
{
    /** @var array<string> */
    public array $splitInput = [];

    protected function createAudioFormat(): DefaultAudio
    {
        return new Mp3();
    }

    protected function splitAudioIntoChunks(string $mp3FilePath): Generator
    {
        $this->splitInput[] = $mp3FilePath;
        yield 'chunk-a';
        yield 'chunk-b';
    }

    protected function convertRecordingToMp3(string $recordingFileKey): string
    {
        return '/converted/' . $recordingFileKey . '.mp3';
    }
}
