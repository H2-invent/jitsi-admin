<?php
declare(strict_types=1);

namespace App\Service\Transcription\Provider\Mistral;

use App\Entity\Server;
use Partitech\PhpMistral\Clients\Mistral\MistralClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class VoxtralMiniClientFactory
{
    public function __construct(
        #[Autowire(param: 'app.transcription.mistral.uri')]
        private readonly string $mistralUri,
    )
    {
    }

    public function create(Server $server): MistralClient
    {
        $apiKey = $server->getApiKeyTranscription();

        return new MistralClient($apiKey, $this->mistralUri);
    }
}
