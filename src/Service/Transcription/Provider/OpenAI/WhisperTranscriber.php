<?php
declare(strict_types=1);

namespace App\Service\Transcription\Provider\OpenAI;

use App\Entity\Server;
use App\Service\Transcription\Provider\AbstractTranscriber;
use GuzzleHttp;
use OpenAI;
use OpenAI\Client;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class WhisperTranscriber extends AbstractTranscriber
{
    public function __construct(
        private readonly OpenAI\Factory $clientFactory,
        #[Autowire(param: 'app.transcription.openai.uri')]
        private readonly string $openApiUri,
    ) {
    }

    protected function createClient(Server $server): Client
    {
        return $this->clientFactory
            ->withApiKey($server->getApiKeyTranscription())
            ->withBaseUri($this->openApiUri)
            ->withHttpClient(new GuzzleHttp\Client([
                'connect_timeout' => 0,
                'read_timeout' => 0,
                'timeout' => 0,
            ]))
            ->make();
    }

    protected function transcribeChunk(string $chunkPath, mixed $client): string
    {
        /** @var Client $client */
        $response = $client->audio()->transcribe([
            'model' => 'whisper-1',
            'file' => fopen($chunkPath, 'rb'),
            'response_format' => 'text',
        ]);

        return $response->text;
    }
}
