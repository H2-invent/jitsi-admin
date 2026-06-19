<?php
declare(strict_types=1);

namespace App\Service\Transcription;

use App\Entity\Server;
use Generator;
use GuzzleHttp;
use OpenAI;
use Symfony\Component\String\UnicodeString;

class Transcriber
{
    public function __construct(
        private readonly OpenAI\Factory $clientFactory,
    )
    {
    }

    /**
     * @return array{0: string, 1: array<string>}
     */
    public function transcribeAudioChunks(Generator $audioChunks, ?Server $server): array
    {
        $client = $this->clientFactory
            ->withApiKey($server->getApiKeyOpenAI())
            ->withHttpClient(new GuzzleHttp\Client([
                'connect_timeout' => 0,
                'read_timeout' => 0,
                'timeout' => 0,
            ]))
            ->make()
        ;
        $text = '';
        $chunks = [];
        foreach ($audioChunks as $chunk) {
            $chunks[] = $chunk;
            $transcription = $this->transcribeChunk($chunk, $client);
            $transcription = rtrim($transcription); // all chunks have a newline at the end, remove it
            $text .= $transcription;
        }

        // every sentence should be a new line or else we have a file with a single line of long text
        $sentences = preg_split('/(?<=[.?!])\s+/', $text, flags: PREG_SPLIT_NO_EMPTY);
        $text = implode("\n", $sentences);

        return [$text, $chunks];
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
