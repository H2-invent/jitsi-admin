<?php
declare(strict_types=1);

namespace App\Tests\Service\Transcription;

use App\Entity\Server;
use App\Service\Transcription\Provider\Mistral\MistralVoxtralMiniProvider;
use App\Service\Transcription\Provider\Mistral\VoxtralMiniMediaConverter;
use App\Service\Transcription\Provider\Mistral\VoxtralMiniTranscriber;
use App\Service\Transcription\Provider\OpenAI\OpenAIWhisperProvider;
use App\Service\Transcription\Provider\OpenAI\WhisperMediaConverter;
use App\Service\Transcription\Provider\OpenAI\WhisperTranscriber;
use App\Service\Transcription\TranscriptionProvider;
use App\Service\Transcription\TranscriptionProviderResolver;
use FFMpeg\Format\Audio\DefaultAudio;
use FFMpeg\Format\Audio\Mp3;
use Generator;
use OpenAI\Client as OpenAIClient;
use Partitech\PhpMistral\Clients\Mistral\MistralClient;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TranscriptionProviderResolverTest extends TestCase
{
    private TranscriptionProviderResolver $resolver;
    private OpenAIWhisperProvider $whisperProvider;
    private MistralVoxtralMiniProvider $voxtralMiniProvider;

    protected function setUp(): void
    {
        $this->whisperProvider = new OpenAIWhisperProvider(
            new ResolverTestWhisperConverter(),
            new ResolverTestWhisperTranscriber(),
        );
        $this->voxtralMiniProvider = new MistralVoxtralMiniProvider(
            new ResolverTestVoxtralConverter(),
            new ResolverTestVoxtralTranscriber(),
        );

        $this->resolver = new TranscriptionProviderResolver($this->whisperProvider, $this->voxtralMiniProvider);
    }

    public function testResolveReturnsWhisperProvider(): void
    {
        $server = (new Server())->setTranscriptionProvider(TranscriptionProvider::OPEN_AI_WHISPER);

        $this->assertSame($this->whisperProvider, $this->resolver->resolve($server));
    }

    public function testResolveReturnsVoxtralMiniProvider(): void
    {
        $server = (new Server())->setTranscriptionProvider(TranscriptionProvider::MISTRAL_VOXTRAL_MINI);

        $this->assertSame($this->voxtralMiniProvider, $this->resolver->resolve($server));
    }

    public function testResolveThrowsForUnsupportedProvider(): void
    {
        $server = (new Server())->setTranscriptionProvider(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported Transcription Provider in Settings');

        $this->resolver->resolve($server);
    }
}

class ResolverTestVoxtralConverter extends VoxtralMiniMediaConverter
{
    public function __construct()
    {
    }

    protected function createAudioFormat(): DefaultAudio
    {
        return new Mp3();
    }
}

class ResolverTestVoxtralTranscriber extends VoxtralMiniTranscriber
{
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

class ResolverTestWhisperConverter extends WhisperMediaConverter
{
    public function __construct()
    {
    }

    protected function createAudioFormat(): DefaultAudio
    {
        return new Mp3();
    }
}

class ResolverTestWhisperTranscriber extends WhisperTranscriber
{
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
