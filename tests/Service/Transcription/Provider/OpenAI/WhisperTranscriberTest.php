<?php
declare(strict_types=1);

namespace App\Tests\Service\Transcription\Provider\OpenAI;

use App\Entity\Server;
use App\Service\Transcription\Provider\OpenAI\WhisperTranscriber;
use GuzzleHttp\Client as GuzzleClient;
use OpenAI\Client as OpenAIClient;
use OpenAI\Factory as OpenAIFactory;
use OpenAI\Responses\Audio\TranscriptionResponse;
use OpenAI\Testing\ClientFake;
use OpenAI\Transporters\HttpTransporter;
use PHPUnit\Framework\TestCase;

class WhisperTranscriberTest extends TestCase
{
    public function testCreateClientConfiguresApiKeyAndBaseUri(): void
    {
        $server = (new Server())->setApiKeyTranscription('openai-secret');
        $transcriber = new WhisperTranscriber(new OpenAIFactory(), 'https://openai.example/v1');

        $client = $this->invokeProtected($transcriber, 'createClient', [$server]);

        $this->assertInstanceOf(OpenAIClient::class, $client);

        $transporter = $this->readProperty($client, OpenAIClient::class, 'transporter');
        $this->assertInstanceOf(HttpTransporter::class, $transporter);

        $baseUri = $this->readProperty($transporter, HttpTransporter::class, 'baseUri');
        $this->assertSame('https://openai.example/v1/', $baseUri->toString());

        $headers = $this->readProperty($transporter, HttpTransporter::class, 'headers');
        $this->assertSame('Bearer openai-secret', $headers->toArray()['Authorization']);

        $httpClient = $this->readProperty($transporter, HttpTransporter::class, 'client');
        $this->assertInstanceOf(GuzzleClient::class, $httpClient);
    }

    public function testTranscribeChunkUsesWhisperModelAndReturnsText(): void
    {
        $chunkPath = tempnam(sys_get_temp_dir(), 'whisper_chunk_');
        file_put_contents($chunkPath, 'audio-bytes');

        $fake = new ClientFake([
            TranscriptionResponse::fake(['text' => 'Hello Whisper']),
        ]);
        $transcriber = new WhisperTranscriber(new OpenAIFactory(), 'https://openai.example/v1');

        try {
            $result = $this->invokeProtected($transcriber, 'transcribeChunk', [$chunkPath, $fake]);

            $this->assertSame('Hello Whisper', $result);

            $fake->audio()->assertSent(function (string $method, array $parameters): bool {
                return $method === 'transcribe'
                    && $parameters['model'] === 'whisper-1'
                    && $parameters['response_format'] === 'text'
                    && is_resource($parameters['file']);
            });
        } finally {
            if (file_exists($chunkPath)) {
                unlink($chunkPath);
            }
        }
    }

    private function invokeProtected(object $object, string $method, array $arguments = []): mixed
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $arguments);
    }

    private function readProperty(object $object, string $class, string $property): mixed
    {
        $reflection = new \ReflectionProperty($class, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
