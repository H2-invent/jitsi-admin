<?php
declare(strict_types=1);

namespace App\Tests\Service\Transcription\Provider\Mistral;

use App\Entity\Server;
use App\Service\Transcription\Provider\Mistral\VoxtralMiniClientFactory;
use App\Service\Transcription\Provider\Mistral\VoxtralMiniTranscriber;
use Partitech\PhpMistral\Clients\Mistral\MistralClient;
use PHPUnit\Framework\TestCase;

class VoxtralMiniTranscriberTest extends TestCase
{
    public function testCreateClientUsesFactoryWithServer(): void
    {
        $server = (new Server())->setApiKeyTranscription('mistral-key');
        $transcriber = new VoxtralMiniTranscriber(new VoxtralMiniClientFactory('https://mistral.example/v1'));

        $client = $this->invokeProtected($transcriber, 'createClient', [$server]);

        $this->assertInstanceOf(MistralClient::class, $client);

        $reflection = new \ReflectionClass($client);
        $apiKey = $reflection->getProperty('apiKey');
        $apiKey->setAccessible(true);
        $this->assertSame('mistral-key', $apiKey->getValue($client));
    }

    public function testTranscribeChunkPostsAudioFileAndReturnsText(): void
    {
        $chunkPath = tempnam(sys_get_temp_dir(), 'voxtral_chunk_');
        file_put_contents($chunkPath, 'audio-bytes');

        $capturedParameters = null;
        $client = $this->getMockBuilder(MistralClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['request'])
            ->getMock();
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'v1/audio/transcriptions',
                $this->callback(function (array $parameters) use (&$capturedParameters): bool {
                    $capturedParameters = $parameters;
                    return true;
                }),
            )
            ->willReturn(['text' => 'Hello Voxtral']);

        try {
            $transcriber = new VoxtralMiniTranscriber(new VoxtralMiniClientFactory('https://mistral.example/v1'));

            $result = $this->invokeProtected($transcriber, 'transcribeChunk', [$chunkPath, $client]);

            $this->assertSame('Hello Voxtral', $result);
            $this->assertIsArray($capturedParameters);
            $this->assertSame('voxtral-mini-latest', $capturedParameters['model']);
            $this->assertIsResource($capturedParameters['file']);
            $this->assertSame('audio-bytes', stream_get_contents($capturedParameters['file']));
        } finally {
            if (is_resource($capturedParameters['file'] ?? null)) {
                fclose($capturedParameters['file']);
            }
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
}
