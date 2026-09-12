<?php
declare(strict_types=1);

namespace App\Tests\Service\Transcription\Provider\Mistral;

use App\Service\Transcription\Provider\Mistral\VoxtralMiniMediaConverter;
use App\Tests\Support\AudioMockTrait;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Audio\Mp3;
use Gaufrette\FilesystemInterface;
use PHPUnit\Framework\TestCase;

class VoxtralMiniMediaConverterTest extends TestCase
{
    use AudioMockTrait;
    public function testCreateAudioFormatUsesMono192KbitMp3(): void
    {
        $converter = new VoxtralMiniMediaConverter(
            $this->createMock(FFMpeg::class),
            $this->createMock(FilesystemInterface::class),
        );

        $format = $this->invokeProtected($converter, 'createAudioFormat');

        $this->assertInstanceOf(Mp3::class, $format);
        $this->assertSame(1, $format->getAudioChannels());
        $this->assertSame(192, $format->getAudioKiloBitrate());
    }

    /**
     * @dataProvider splitAudioIntoChunksProvider
     */
    public function testSplitAudioIntoChunks(float $duration, int $expectedChunks): void
    {
        $savedPaths = [];
        $ffmpeg = $this->createMock(FFMpeg::class);
        $ffmpeg->method('open')->willReturn($this->createAudioMock($duration, $savedPaths));

        $converter = new VoxtralMiniMediaConverter($ffmpeg, $this->createMock(FilesystemInterface::class));

        try {
            $chunks = iterator_to_array(
                $this->invokeProtected($converter, 'splitAudioIntoChunks', ['/tmp/source.mp3']),
            );

            $this->assertCount($expectedChunks, $chunks);
            $this->assertSame($chunks, array_values(array_unique($chunks)));

            if ($expectedChunks === 1) {
                $this->assertSame(['/tmp/source.mp3'], $chunks);
                $this->assertSame([], $savedPaths);
                return;
            }

            $this->assertNotContains('/tmp/source.mp3', $chunks);
            $this->assertCount($expectedChunks, $savedPaths);
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
        }
    }

    public static function splitAudioIntoChunksProvider(): array
    {
        return [
            'short recording' => [100.0, 1],
            'exactly at the limit' => [10800.0, 1],
            'just over the limit' => [10801.0, 2],
            'three chunks' => [21601.0, 3],
        ];
    }

    private function invokeProtected(object $object, string $method, array $arguments = []): mixed
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $arguments);
    }
}
