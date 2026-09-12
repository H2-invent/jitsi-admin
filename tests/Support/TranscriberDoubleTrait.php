<?php
declare(strict_types=1);

namespace App\Tests\Support;

use App\Entity\Server;
use Generator;

trait TranscriberDoubleTrait
{
    public ?Server $server = null;

    /** @var array<string> */
    public array $receivedChunks = [];

    public string $text = '';

    public string $delta = 'transcriber-output';

    public function transcribeAudioChunks(Generator $audioChunks, Server $server): array
    {
        $this->server = $server;
        $this->receivedChunks = iterator_to_array($audioChunks);

        return [$this->text, $this->delta];
    }
}
