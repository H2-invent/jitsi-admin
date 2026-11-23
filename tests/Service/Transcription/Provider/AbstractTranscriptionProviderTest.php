<?php
declare(strict_types=1);

namespace App\Tests\Service\Transcription\Provider;

use App\Entity\Server;
use App\Service\Transcription\Provider\AbstractMediaConverter;
use App\Service\Transcription\Provider\AbstractTranscriber;
use App\Service\Transcription\Provider\AbstractTranscriptionProvider;
use FFMpeg\Format\Audio\DefaultAudio;
use FFMpeg\Format\Audio\Mp3;
use Generator;
use PHPUnit\Framework\TestCase;

class AbstractTranscriptionProviderTest extends TestCase
{
    public function testYieldAudioChunksDelegatesToConverter(): void
    {
        $converter = new AbstractTranscriptionProviderTestConverter();
        $converter->chunksToYield = ['chunk-1', 'chunk-2'];
        $provider = new AbstractTranscriptionProviderTestProvider(
            $converter,
            new AbstractTranscriptionProviderTestTranscriber(),
        );

        $chunks = iterator_to_array($provider->yieldAudioChunks('recording.webm'));

        $this->assertSame('recording.webm', $converter->requestedKey);
        $this->assertSame(['chunk-1', 'chunk-2'], $chunks);
    }

    public function testTranscribeChunksDelegatesToTranscriber(): void
    {
        $converter = new AbstractTranscriptionProviderTestConverter();
        $transcriber = new AbstractTranscriptionProviderTestTranscriber();
        $transcriber->result = 'combined text';
        $transcriber->resultChunks = ['chunk-1'];
        $provider = new AbstractTranscriptionProviderTestProvider($converter, $transcriber);
        $server = new Server();

        $result = $provider->transcribeChunks($this->generator(['chunk-1']), $server);

        $this->assertSame(['combined text', ['chunk-1']], $result);
        $this->assertSame($server, $transcriber->server);
        $this->assertSame(['chunk-1'], $transcriber->receivedChunks);
    }

    public function testDeleteChunksDelegatesToConverter(): void
    {
        $converter = new AbstractTranscriptionProviderTestConverter();
        $provider = new AbstractTranscriptionProviderTestProvider(
            $converter,
            new AbstractTranscriptionProviderTestTranscriber(),
        );

        $provider->deleteChunks(['a.mp3', 'b.mp3']);

        $this->assertSame([['a.mp3', 'b.mp3']], $converter->deleteCalls);
    }

    private function generator(array $items): Generator
    {
        foreach ($items as $item) {
            yield $item;
        }
    }
}

class AbstractTranscriptionProviderTestConverter extends AbstractMediaConverter
{
    /** @var array<string> */
    public array $chunksToYield = [];

    /** @var array<array<string>> */
    public array $deleteCalls = [];

    public ?string $requestedKey = null;

    public function __construct()
    {
    }

    protected function createAudioFormat(): DefaultAudio
    {
        return new Mp3();
    }

    protected function splitAudioIntoChunks(string $mp3FilePath): Generator
    {
        yield from $this->chunksToYield;
    }

    public function yieldChunksOfRecording(string $recordingFileKey): Generator
    {
        $this->requestedKey = $recordingFileKey;
        yield from $this->chunksToYield;
    }

    public function deleteChunks(array $chunkPaths): void
    {
        $this->deleteCalls[] = $chunkPaths;
    }
}

class AbstractTranscriptionProviderTestTranscriber extends AbstractTranscriber
{
    public ?Server $server = null;

    /** @var array<string> */
    public array $receivedChunks = [];

    public string $result = '';

    /** @var array<string> */
    public array $resultChunks = [];

    public function __construct()
    {
    }

    protected function createClient(Server $server): mixed
    {
        return null;
    }

    protected function transcribeChunk(string $chunkPath, mixed $client): string
    {
        return '';
    }

    public function transcribeAudioChunks(Generator $audioChunks, Server $server): array
    {
        $this->server = $server;
        $this->receivedChunks = iterator_to_array($audioChunks);

        return [$this->result, $this->resultChunks];
    }
}

class AbstractTranscriptionProviderTestProvider extends AbstractTranscriptionProvider
{
    public function __construct(
        AbstractMediaConverter $converter,
        AbstractTranscriber $transcriber,
    ) {
        parent::__construct($converter, $transcriber);
    }
}
