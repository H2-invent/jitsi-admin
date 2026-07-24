<?php
declare(strict_types=1);

namespace App\Service\Transcription\Provider\OpenAI;

use App\Entity\Server;
use Generator;
use GuzzleHttp;
use OpenAI;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class WhisperTranscriber
{
    public function __construct(
        private readonly OpenAI\Factory $clientFactory,
        #[Autowire(param: 'app.transcription.openai.uri')]
        private readonly string $openApiUri,
    )
    {
    }

    /**
     * @return array{0: string, 1: array<string>}
     */
    public function transcribeAudioChunks(Generator $audioChunks, ?Server $server): array
    {
        $client = $this->createClient($server);
        $text = '';
        $chunks = [];
        $firstChunk = true;
        foreach ($audioChunks as $chunk) {
            $chunks[] = $chunk;
            $transcription = $this->transcribeChunk($chunk, $client);
            $transcription = rtrim($transcription); // all chunks have a newline at the end, remove it
            if ($firstChunk) {
                $text .= ' '; // add space between chunks so sentences can be split after
            }
            $text .= $transcription;

            $firstChunk = false;
        }

        // every sentence should be a new line or else we have a file with a single line of long text
        $sentences = preg_split('/(?<=[.?!])\s+/', $text, flags: PREG_SPLIT_NO_EMPTY);
        $text = implode("\n", $sentences);

        return [$text, $chunks];
    }

    private function createClient(?Server $server): OpenAI\Client
    {
        return $this->clientFactory
            ->withApiKey($server->getApiKeyTranscription())
            ->withBaseUri($this->openApiUri)
            ->withHttpClient(new GuzzleHttp\Client([
                    'connect_timeout' => 0,
                    'read_timeout' => 0,
                    'timeout' => 0,
                ])
            )
            ->make()
        ;
    }

    private function transcribeChunk(string $chunkPath, OpenAI\Client $client): string
    {
        $response = $client->audio()->transcribe([
            'model' => 'whisper-1',
            'file' => fopen($chunkPath, 'rb'),
            'response_format' => 'text',
        ]);

        return $response->text;
    }
}
