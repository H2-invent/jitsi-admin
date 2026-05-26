<?php
declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\LokiLogMessage;
use Itspire\MonologLoki\Handler\LokiHandler;
use JsonException;
use Monolog\Handler\HandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class LokiLogMessageHandler
{
    public function __construct(
        #[Autowire(service: 'monolog.handler.loki')]
        private readonly HandlerInterface $lokiHandler,
    )
    {
    }

    /**
     * @throws JsonException
     */
    public function __invoke(LokiLogMessage $message): void
    {
        $this->lokiHandler->handleBatch($message->getRecords());
    }
}
