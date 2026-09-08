<?php
declare(strict_types=1);

namespace App\Service\Transcription\Provider;

use App\Entity\Server;
use Generator;

abstract class AbstractTranscriber
{
    /**
     * @return array{0: string, 1: array<string>}
     */
    public function transcribeAudioChunks(Generator $audioChunks, Server $server): array
    {
        $client = $this->createClient($server);

        [$text, $chunks] = $this->processChunks(
            $audioChunks,
            fn(string $chunk) => rtrim($this->transcribeChunk($chunk, $client))
        );

        $text = $this->formatAsSentences($text);

        return [$text, $chunks];
    }

    /**
     * Create the provider-specific API client.
     */
    abstract protected function createClient(Server $server): mixed;

    /**
     * Transcribe a single audio chunk using the provider-specific client.
     */
    abstract protected function transcribeChunk(string $chunkPath, mixed $client): string;

    /**
     * Iterates through chunks and concatenates transcriptions.
     *
     * @return array{0: string, 1: array<string>}
     */
    protected function processChunks(Generator $audioChunks, callable $transcribeCallback): array
    {
        $text = '';
        $chunks = [];
        $firstChunk = true;

        foreach ($audioChunks as $chunk) {
            $chunks[] = $chunk;
            $transcription = $transcribeCallback($chunk);

            if (!$firstChunk) {
                $text .= ' ';
            }
            $text .= $transcription;
            $firstChunk = false;
        }

        return [$text, $chunks];
    }

    /**
     * Splits text into sentences (one per line).
     */
    protected function formatAsSentences(string $text): string
    {
        $text = rtrim($text);
        $sentences = preg_split('/(?<=[.?!])\s+/', $text, flags: PREG_SPLIT_NO_EMPTY);
        return implode("\n", $sentences);
    }
}

