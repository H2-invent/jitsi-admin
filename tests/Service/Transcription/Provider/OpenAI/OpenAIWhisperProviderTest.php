<?php
declare(strict_types=1);

namespace App\Tests\Service\Transcription\Provider\OpenAI;

use App\Entity\Server;
use App\Service\Transcription\Provider\OpenAI\OpenAIWhisperProvider;
use App\Service\Transcription\Provider\OpenAI\WhisperMediaConverter;
use App\Service\Transcription\Provider\OpenAI\WhisperTranscriber;
use App\Tests\Support\MediaConverterDoubleTrait;
use App\Tests\Support\TranscriberDoubleTrait;
use Generator;
use OpenAI\Client as OpenAIClient;
use PHPUnit\Framework\TestCase;

class OpenAIWhisperProviderTest extends TestCase
{
    public function testProviderDelegatesToInjectedConverterAndTranscriber(): void
    {
        $converter = new OpenAIWhisperProviderTestConverter();
        $converter->chunks = ['chunk-a', 'chunk-b'];

        $transcriber = new OpenAIWhisperProviderTestTranscriber();
        $transcriber->text = 'Whisper transcript';
        $transcriber->delta = 'whisper-transcriber-output';

        $provider = new OpenAIWhisperProvider($converter, $transcriber);
        $server = new Server();

        $chunks = iterator_to_array($provider->yieldAudioChunks('recording.webm'));
        $this->assertSame('recording.webm', $converter->requestedKey);
        $this->assertSame(['chunk-a', 'chunk-b'], $chunks);

        $result = $provider->transcribeChunks($this->generator(['chunk-a', 'chunk-b']), $server);
        $this->assertSame(['Whisper transcript', 'whisper-transcriber-output'], $result);
        $this->assertSame($server, $transcriber->server);
        $this->assertSame(['chunk-a', 'chunk-b'], $transcriber->receivedChunks);

        $provider->deleteChunks(['chunk-a']);
        $this->assertSame([['chunk-a']], $converter->deleted);
    }

    private function generator(array $items): Generator
    {
        foreach ($items as $item) {
            yield $item;
        }
    }
}

class OpenAIWhisperProviderTestConverter extends WhisperMediaConverter
{
    use MediaConverterDoubleTrait;

    public function __construct()
    {
    }
}

class OpenAIWhisperProviderTestTranscriber extends WhisperTranscriber
{
    use TranscriberDoubleTrait;

    public function __construct()
    {
    }

    protected function createClient(Server $server): OpenAIClient
    {
        throw new \LogicException('Not used in this test');
    }

    protected function transcribeChunk(string $chunkPath, mixed $client): string
    {
        return '';
    }
}
