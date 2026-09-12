<?php
declare(strict_types=1);

namespace App\Tests\Service\Transcription\Provider;

use App\Entity\Server;
use App\Service\Transcription\Provider\AbstractTranscriber;
use Generator;
use PHPUnit\Framework\TestCase;

class AbstractTranscriberTest extends TestCase
{
    public function testTranscribeAudioChunksCreatesClientOnceAndFormatsSentences(): void
    {
        $transcriber = new AbstractTranscriberTestDouble([
            'a.mp3' => 'Hello world. ',
            'b.mp3' => 'How are you? ',
            'c.mp3' => ' Fine!',
        ]);
        $server = new Server();

        [$text, $chunks] = $transcriber->transcribeAudioChunks(
            $this->generator(['a.mp3', 'b.mp3', 'c.mp3']),
            $server,
        );

        $this->assertSame("Hello world.\nHow are you?\nFine!", $text);
        $this->assertSame(['a.mp3', 'b.mp3', 'c.mp3'], $chunks);
        $this->assertCount(1, $transcriber->clients);
        $this->assertSame($server, $transcriber->clients[0]);
        $this->assertSame(['a.mp3', 'b.mp3', 'c.mp3'], array_column($transcriber->transcribed, 0));
        $this->assertSame($transcriber->transcribed[0][1], $transcriber->transcribed[2][1]);
    }

    public function testTranscribeAudioChunksWithNoChunksReturnsEmptyResult(): void
    {
        $transcriber = new AbstractTranscriberTestDouble([]);

        [$text, $chunks] = $transcriber->transcribeAudioChunks($this->generator([]), new Server());

        $this->assertSame('', $text);
        $this->assertSame([], $chunks);
        $this->assertCount(1, $transcriber->clients);
        $this->assertSame([], $transcriber->transcribed);
    }

    public function testProcessChunksJoinsWithSingleSpacesAndKeepsChunkList(): void
    {
        $transcriber = new AbstractTranscriberTestDouble([]);
        $calls = [];

        [$text, $chunks] = $transcriber->callProcessChunks(
            $this->generator(['x', 'y', 'z']),
            function (string $chunk) use (&$calls): string {
                $calls[] = $chunk;
                return 'T' . $chunk;
            },
        );

        $this->assertSame(['x', 'y', 'z'], $calls);
        $this->assertSame('Tx Ty Tz', $text);
        $this->assertSame(['x', 'y', 'z'], $chunks);
    }

    public function testProcessChunksWithEmptyGeneratorReturnsEmptyResult(): void
    {
        $transcriber = new AbstractTranscriberTestDouble([]);

        [$text, $chunks] = $transcriber->callProcessChunks(
            $this->generator([]),
            fn(string $chunk): string => $chunk,
        );

        $this->assertSame('', $text);
        $this->assertSame([], $chunks);
    }

    /**
     * @dataProvider formatAsSentencesProvider
     */
    public function testFormatAsSentences(string $input, string $expected): void
    {
        $transcriber = new AbstractTranscriberTestDouble([]);

        $this->assertSame($expected, $transcriber->callFormatAsSentences($input));
    }

    public static function formatAsSentencesProvider(): array
    {
        return [
            'multiple sentence endings' => ['One. Two! Three?', "One.\nTwo!\nThree?"],
            'no punctuation' => ['SingleSentence', 'SingleSentence'],
            'empty string' => ['', ''],
            'only whitespace' => ['   ', ''],
            'trailing whitespace trimmed' => ['Trailing space.   ', 'Trailing space.'],
            'newlines between sentences' => ["A.\n\nB.", "A.\nB."],
            'comma does not split' => ['a, b, c.', 'a, b, c.'],
        ];
    }

    private function generator(array $items): Generator
    {
        foreach ($items as $item) {
            yield $item;
        }
    }
}

class AbstractTranscriberTestDouble extends AbstractTranscriber
{
    /** @var array<Server> */
    public array $clients = [];

    /** @var array<array{0: string, 1: mixed}> */
    public array $transcribed = [];

    public function __construct(private readonly array $responses)
    {
    }

    protected function createClient(Server $server): mixed
    {
        $this->clients[] = $server;

        return new \stdClass();
    }

    protected function transcribeChunk(string $chunkPath, mixed $client): string
    {
        $this->transcribed[] = [$chunkPath, $client];

        return $this->responses[$chunkPath] ?? '';
    }

    public function callProcessChunks(Generator $audioChunks, callable $transcribeCallback): array
    {
        return $this->processChunks($audioChunks, $transcribeCallback);
    }

    public function callFormatAsSentences(string $text): string
    {
        return $this->formatAsSentences($text);
    }
}
