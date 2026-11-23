<?php
declare(strict_types=1);

namespace App\Tests\Service\Transcription\Provider\Mistral;

use App\Entity\Server;
use App\Service\Transcription\Provider\Mistral\MistralVoxtralMiniProvider;
use App\Service\Transcription\Provider\Mistral\VoxtralMiniMediaConverter;
use App\Service\Transcription\Provider\Mistral\VoxtralMiniTranscriber;
use App\Tests\Support\MediaConverterDoubleTrait;
use App\Tests\Support\TranscriberDoubleTrait;
use Generator;
use Partitech\PhpMistral\Clients\Mistral\MistralClient;
use PHPUnit\Framework\TestCase;

class MistralVoxtralMiniProviderTest extends TestCase
{
    public function testProviderDelegatesToInjectedConverterAndTranscriber(): void
    {
        $converter = new MistralVoxtralMiniProviderTestConverter();
        $converter->chunks = ['chunk-a', 'chunk-b'];

        $transcriber = new MistralVoxtralMiniProviderTestTranscriber();
        $transcriber->text = 'Voxtral transcript';
        $transcriber->delta = 'voxtral-transcriber-output';

        $provider = new MistralVoxtralMiniProvider($converter, $transcriber);
        $server = new Server();

        $chunks = iterator_to_array($provider->yieldAudioChunks('recording.webm'));
        $this->assertSame('recording.webm', $converter->requestedKey);
        $this->assertSame(['chunk-a', 'chunk-b'], $chunks);

        $result = $provider->transcribeChunks($this->generator(['chunk-a', 'chunk-b']), $server);
        $this->assertSame(['Voxtral transcript', 'voxtral-transcriber-output'], $result);
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

class MistralVoxtralMiniProviderTestConverter extends VoxtralMiniMediaConverter
{
    use MediaConverterDoubleTrait;

    public function __construct()
    {
    }
}

class MistralVoxtralMiniProviderTestTranscriber extends VoxtralMiniTranscriber
{
    use TranscriberDoubleTrait;

    public function __construct()
    {
    }

    protected function createClient(Server $server): MistralClient
    {
        throw new \LogicException('Not used in this test');
    }

    protected function transcribeChunk(string $chunkPath, mixed $client): string
    {
        return '';
    }
}
