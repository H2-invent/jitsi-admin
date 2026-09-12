<?php
declare(strict_types=1);

namespace App\Tests\Service\Transcription\Provider\OpenAI;

use App\Service\Transcription\Provider\OpenAI\WhisperMediaConverter;
use App\Tests\Support\AudioMockTrait;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Audio\Mp3;
use Gaufrette\FilesystemInterface;
use PHPUnit\Framework\TestCase;

class WhisperMediaConverterTest extends TestCase
{
    use AudioMockTrait;
    public function testCreateAudioFormatUsesMono128KbitMp3(): void
    {
        $converter = new WhisperMediaConverter(
            $this->createMock(FFMpeg::class),
            $this->createMock(FilesystemInterface::class),
        );

        $format = $this->invokeProtected($converter, 'createAudioFormat');

        $this->assertInstanceOf(Mp3::class, $format);
        $this->assertSame(1, $format->getAudioChannels());
        $this->assertSame(128, $format->getAudioKiloBitrate());
    }

    public function testSplitAudioIntoChunksKeepsSmallFileUntouched(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'whisper_small_');
        file_put_contents($filePath, str_repeat('a', 1024));

        $savedPaths = [];
        $ffmpeg = $this->createMock(FFMpeg::class);
        $ffmpeg->method('open')->willReturn($this->createAudioMock(10.0, $savedPaths));
        $converter = new WhisperMediaConverter($ffmpeg, $this->createMock(FilesystemInterface::class));

        try {
            $chunks = iterator_to_array(
                $this->invokeProtected($converter, 'splitAudioIntoChunks', [$filePath]),
            );

            $this->assertSame([$filePath], $chunks);
            $this->assertSame([], $savedPaths);
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    public function testSplitAudioIntoChunksSplitsLargeFile(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'whisper_large_');
        $handle = fopen($filePath, 'w');
        ftruncate($handle, 100 * 1024 * 1024);
        fclose($handle);

        $savedPaths = [];
        $ffmpeg = $this->createMock(FFMpeg::class);
        $ffmpeg->method('open')->willReturn($this->createAudioMock(10000.0, $savedPaths));
        $converter = new WhisperMediaConverter($ffmpeg, $this->createMock(FilesystemInterface::class));

        try {
            $chunks = iterator_to_array(
                $this->invokeProtected($converter, 'splitAudioIntoChunks', [$filePath]),
            );

            $this->assertCount(5, $chunks);
            $this->assertNotContains($filePath, $chunks);
            $this->assertSame($chunks, array_values(array_unique($chunks)));
            $this->assertCount(5, $savedPaths);
            foreach ($chunks as $chunk) {
                $this->assertStringEndsWith('.mp3', $chunk);
                $this->assertFileExists($chunk);
            }
        } finally {
            foreach ($savedPaths as $savedPath) {
                if (file_exists($savedPath)) {
                    unlink($savedPath);
                }
            }
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    private function invokeProtected(object $object, string $method, array $arguments = []): mixed
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $arguments);
    }
}
