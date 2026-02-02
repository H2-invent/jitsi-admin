<?php

namespace App\Command;

use App\Message\TestMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

#[AsCommand(
    name: 'app:amqp:test',
    description: 'Test RabbitMQ/AMQP connection by dispatching and consuming a message',
)]
class AmqpTestCommand extends Command
{
    private const WAIT_MICROSECONDS = 100_000; // 100ms
    private const MAX_RETRIES = 10;

    public function __construct(
        private MessageBusInterface $messageBus,
        private ReceiverInterface $testTransport,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // verify transport is AMQP
        $transportClass = get_class($this->testTransport);
        if (!str_contains($transportClass, 'Amqp')) {
            $io->error("Transport is not AMQP! Found: $transportClass");

            return Command::FAILURE;
        }

        $randomId = rand(0, PHP_INT_MAX);
        $this->messageBus->dispatch(
            new TestMessage($randomId),
            [new TransportNamesStamp(['test'])]
        );

        // small delay to ensure message is queued
        usleep(self::WAIT_MICROSECONDS);

        // try multiple times as there might be other messages stuck in the queue
        for ($i = 0; $i <= self::MAX_RETRIES; $i++) {
            foreach ($this->testTransport->get() as $envelope) {
                $message = $envelope->getMessage();

                if ($message instanceof TestMessage && $message->randomNumber === $randomId) {
                    $this->testTransport->ack($envelope);
                    $io->success("Successfully dispatched and consumed test message!");

                    return Command::SUCCESS;
                }

                // reject other test messages that might have been stuck
                $this->testTransport->reject($envelope);
            }
        }

        $io->error("Failed to retrieve test message from queue");

        return Command::FAILURE;
    }
}
