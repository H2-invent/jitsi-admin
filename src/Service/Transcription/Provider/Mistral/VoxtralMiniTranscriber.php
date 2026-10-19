<?php
declare(strict_types=1);

namespace App\Service\Transcription\Provider\Mistral;

use App\Entity\Server;
use App\Service\Transcription\Provider\AbstractTranscriber;
use Partitech\PhpMistral\Clients\Mistral\MistralClient;

class VoxtralMiniTranscriber extends AbstractTranscriber
{
    public function __construct(
        private readonly VoxtralMiniClientFactory $clientFactory,
    ) {
    }

    protected function createClient(Server $server): MistralClient
    {
        return $this->clientFactory->create($server);
    }

    protected function transcribeChunk(string $chunkPath, mixed $client): string
    {
        /** @var MistralClient $client */
        $response = $client->request('POST', 'v1/audio/transcriptions', [
            'model' => 'voxtral-mini-latest',
            'file' => fopen($chunkPath, 'rb'),
        ]);

        return $response['text'];
    }
}
