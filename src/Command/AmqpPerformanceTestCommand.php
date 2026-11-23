<?php

namespace App\Command;

use App\Message\AmqpPerformanceTestMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransport;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:amqp:performance-test',
    description: 'Benchmark the RabbitMQ/AMQP connection (throughput, latency, errors, message loss) using the "test" transport',
)]
class AmqpPerformanceTestCommand extends Command
{
    private const TRANSPORT_NAME = 'test';
    private const ERROR_BACKOFF_MICROSECONDS = 100_000;
    private const EMPTY_QUEUE_POLL_MICROSECONDS = 1_000;
    private const WORKER_POLL_MICROSECONDS = 10_000;

    private float $testStartedAt;

    public function __construct(
        #[Autowire(service: 'messenger.transport.test')]
        private readonly TransportInterface $transport,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('messages', 'm', InputOption::VALUE_REQUIRED, 'Total number of messages to publish', 1000)
            ->addOption('payload-size', 's', InputOption::VALUE_REQUIRED, 'Payload size per message in bytes', 1024)
            ->addOption('processes', 'p', InputOption::VALUE_REQUIRED, 'Number of parallel publisher processes (each with its own AMQP connection)', 1)
            ->addOption('rate', 'r', InputOption::VALUE_REQUIRED, 'Target publish rate in messages/second across all publishers (0 = as fast as possible)', 0)
            ->addOption('concurrent', 'c', InputOption::VALUE_NONE, 'Consume while publishing (realistic end-to-end latency) instead of publishing first and consuming afterwards')
            ->addOption('consume-timeout', 't', InputOption::VALUE_REQUIRED, 'Seconds to wait for outstanding messages after publishing has finished', 30)
            ->addOption('max-errors', null, InputOption::VALUE_REQUIRED, 'Abort a publisher/consumer after this many errors', 100)
            ->addOption('worker', null, InputOption::VALUE_NONE, 'Internal: run as publisher worker and print JSON results')
            ->addOption('run-id', null, InputOption::VALUE_REQUIRED, 'Internal: run id used by publisher workers')
            ->addOption('offset', null, InputOption::VALUE_REQUIRED, 'Internal: first sequence number used by publisher workers', 0)
            ->setHelp(<<<'HELP'
Publishes messages to the "test" transport and consumes them again to measure the AMQP connection.
Only the "test" queue is used; messages in it that do not belong to the current run are discarded.

Reported metrics: connect time, publish/get/ack latencies (p50/p95/p99), throughput,
end-to-end latency, errors over time, outages, lost and duplicated messages.

Examples:
  <info>%command.full_name%</info>                                  quick baseline (1000 x 1 KiB)
  <info>%command.full_name% -m 20000 -p 8 -c</info>                 8 parallel publishers, consume concurrently
  <info>%command.full_name% -m 60000 -r 200 -c</info>               sustained 200 msg/s for ~5 minutes (stability)
  <info>%command.full_name% -m 2000 -s 262144</info>                large payloads (256 KiB)

Exit code is non-zero if messages were lost or errors occurred.
HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->testStartedAt = microtime(true);

        $messages = (int)$input->getOption('messages');
        $payloadSize = (int)$input->getOption('payload-size');
        $processes = (int)$input->getOption('processes');
        $rate = (float)$input->getOption('rate');
        $maxErrors = max(1, (int)$input->getOption('max-errors'));

        if ($input->getOption('worker')) {
            $result = $this->publish(
                (string)$input->getOption('run-id'),
                (int)$input->getOption('offset'),
                $messages,
                $payloadSize,
                $rate,
                $maxErrors,
                null,
            );
            $output->writeln(json_encode($result, JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);

            return Command::SUCCESS;
        }

        $io = new SymfonyStyle($input, $output);

        if ($messages < 1 || $payloadSize < 0 || $processes < 1 || $rate < 0 || $messages < $processes) {
            $io->error('Invalid options: messages >= processes >= 1, payload-size >= 0 and rate >= 0 required.');

            return Command::INVALID;
        }

        if (!$this->transport instanceof AmqpTransport) {
            $io->error(sprintf('Transport "%s" is not AMQP! Found: %s', self::TRANSPORT_NAME, get_class($this->transport)));

            return Command::FAILURE;
        }

        $concurrent = (bool)$input->getOption('concurrent');
        $consumeTimeout = max(1, (int)$input->getOption('consume-timeout'));
        $runId = bin2hex(random_bytes(8));

        $io->title('AMQP performance test');

        $connectMs = $this->measureConnect();
        if (is_string($connectMs)) {
            $io->error('Could not connect to RabbitMQ: ' . $connectMs);

            return Command::FAILURE;
        }

        $connectionInfo = $this->getConnectionInfo();
        $this->renderSettings($io, $runId, $messages, $payloadSize, $processes, $rate, $concurrent, $connectionInfo);

        $queued = $this->transport->getMessageCount();
        if ($queued > 0) {
            $io->warning(sprintf('%d message(s) already in the "%s" queue. They will be discarded and distort the consume measurement.', $queued, self::TRANSPORT_NAME));
        }

        if (!$concurrent && $processes === 1) {
            $io->section('Publishing');
            $progress = $io->createProgressBar($messages);
            $publishResult = $this->publish($runId, 0, $messages, $payloadSize, $rate, $maxErrors, static fn() => $progress->advance());
            $progress->finish();
            $io->newLine(2);
            $publishResult['connect_ms'] = [$connectMs];
            $consumeResult = $this->consumeAfterPublishing($io, $runId, $publishResult['published'], $consumeTimeout, $maxErrors);
        } else {
            $workers = $this->startWorkers($runId, $messages, $payloadSize, $processes, $rate, $maxErrors);
            $publishResult = null;
            $collect = function () use (&$publishResult, $workers) {
                $publishResult ??= $this->collectWorkerResults($workers);

                return $publishResult;
            };
            // polling process status costs syscalls, so it is throttled to not distort the consume measurement
            $lastCheck = 0.0;
            $running = true;
            $workersRunning = static function () use ($workers, &$lastCheck, &$running): bool {
                if (!$running || microtime(true) - $lastCheck < self::WORKER_POLL_MICROSECONDS / 1e6) {
                    return $running;
                }
                $lastCheck = microtime(true);
                $running = false;
                foreach ($workers as $worker) {
                    if ($worker->isRunning()) {
                        $running = true;
                    }
                }

                return $running;
            };

            if ($concurrent) {
                $io->section(sprintf('Publishing with %d process(es) while consuming', $processes));
                $progress = $io->createProgressBar($messages);
                $consumeResult = $this->consume(
                    $runId,
                    $workersRunning,
                    static fn() => $collect()['published'],
                    $consumeTimeout,
                    $maxErrors,
                    $progress,
                );
                $progress->finish();
                $io->newLine(2);
            } else {
                $io->section(sprintf('Publishing with %d process(es)', $processes));
                while ($workersRunning()) {
                    usleep(self::WORKER_POLL_MICROSECONDS);
                }
                $collect();
                $consumeResult = $this->consumeAfterPublishing($io, $runId, $publishResult['published'], $consumeTimeout, $maxErrors);
            }
            $collect();
        }

        return $this->renderReport($io, $publishResult, $consumeResult, $payloadSize, $rate, $concurrent, $connectionInfo);
    }

    /**
     * @return float|string connect time in ms or the error message
     */
    private function measureConnect(): float|string
    {
        $start = hrtime(true);
        try {
            // opens the connection + channel and declares the queue
            $this->transport->getMessageCount();
        } catch (\Throwable $e) {
            return $this->describeThrowable($e);
        }

        return (hrtime(true) - $start) / 1e6;
    }

    private function publish(string $runId, int $offset, int $count, int $payloadSize, float $rate, int $maxErrors, ?\Closure $onProgress): array
    {
        $connectMs = $this->measureConnect();
        $payload = $payloadSize > 0 ? substr(str_repeat(bin2hex(random_bytes(32)), intdiv($payloadSize, 64) + 1), 0, $payloadSize) : '';
        $intervalNs = $rate > 0 ? 1e9 / $rate : 0;

        $result = [
            'published' => 0,
            'failed' => 0,
            'aborted' => false,
            'latencies' => [],
            'errors' => [],
            'connect_ms' => is_float($connectMs) ? [$connectMs] : [],
            'max_outage_s' => 0.0,
            'unrecovered' => false,
            'started_at' => microtime(true),
            'finished_at' => 0.0,
            'peak_memory' => 0,
        ];
        if (is_string($connectMs)) {
            $this->recordError($result['errors'], 'connect', $connectMs);
        }

        $startNs = hrtime(true);
        $errorCount = 0;
        $outageStartedAt = null;

        for ($i = 0; $i < $count; $i++) {
            if ($intervalNs > 0) {
                $waitNs = $startNs + (int)($i * $intervalNs) - hrtime(true);
                if ($waitNs > 0) {
                    usleep(intdiv($waitNs, 1000));
                }
            }

            $message = new AmqpPerformanceTestMessage($runId, $offset + $i, microtime(true), $payload);
            $sendStart = hrtime(true);
            try {
                $this->transport->send(new Envelope($message));
                $result['latencies'][] = round((hrtime(true) - $sendStart) / 1e6, 3);
                $result['published']++;
                if ($outageStartedAt !== null) {
                    $result['max_outage_s'] = max($result['max_outage_s'], microtime(true) - $outageStartedAt);
                    $outageStartedAt = null;
                }
            } catch (\Throwable $e) {
                $result['failed']++;
                $outageStartedAt ??= microtime(true);
                $this->recordError($result['errors'], 'publish', $this->describeThrowable($e));
                if (++$errorCount >= $maxErrors) {
                    $result['aborted'] = true;
                    break;
                }
                usleep(self::ERROR_BACKOFF_MICROSECONDS);
            }

            if ($onProgress !== null) {
                $onProgress();
            }
        }

        if ($outageStartedAt !== null) {
            $result['unrecovered'] = true;
            $result['max_outage_s'] = max($result['max_outage_s'], microtime(true) - $outageStartedAt);
        }
        $result['finished_at'] = microtime(true);
        $result['peak_memory'] = memory_get_peak_usage(true);

        return $result;
    }

    /**
     * @return Process[]
     */
    private function startWorkers(string $runId, int $messages, int $payloadSize, int $processes, float $rate, int $maxErrors): array
    {
        $workers = [];
        $offset = 0;
        for ($i = 0; $i < $processes; $i++) {
            $count = intdiv($messages, $processes) + ($i < $messages % $processes ? 1 : 0);
            $command = [
                PHP_BINARY,
                $this->projectDir . '/bin/console',
                $this->getName(),
                '--worker',
                '--no-interaction',
                '--run-id=' . $runId,
                '--offset=' . $offset,
                '--messages=' . $count,
                '--payload-size=' . $payloadSize,
                '--rate=' . ($rate / $processes),
                '--max-errors=' . $maxErrors,
                '--env=' . $this->environment,
            ];
            if (!$this->debug) {
                $command[] = '--no-debug';
            }

            $worker = new Process($command, $this->projectDir, null, null, null);
            $worker->start();
            $workers[] = $worker;
            $offset += $count;
        }

        return $workers;
    }

    /**
     * @param Process[] $workers
     */
    private function collectWorkerResults(array $workers): array
    {
        $merged = [
            'published' => 0,
            'failed' => 0,
            'aborted' => false,
            'latencies' => [],
            'errors' => [],
            'connect_ms' => [],
            'max_outage_s' => 0.0,
            'unrecovered' => false,
            'started_at' => PHP_FLOAT_MAX,
            'finished_at' => 0.0,
            'peak_memory' => 0,
        ];

        foreach ($workers as $index => $worker) {
            $worker->wait();
            $result = null;
            $lines = array_filter(explode("\n", $worker->getOutput()), static fn(string $line) => str_starts_with($line, '{'));
            if ($lines !== []) {
                try {
                    $result = json_decode(end($lines), true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                }
            }

            if ($worker->getExitCode() !== 0 || !is_array($result)) {
                $merged['aborted'] = true;
                $this->recordError($merged['errors'], 'worker', sprintf(
                    'Publisher process #%d failed (exit code %s): %s',
                    $index + 1,
                    $worker->getExitCode(),
                    trim(mb_strimwidth($worker->getErrorOutput() ?: $worker->getOutput(), 0, 300, '…')),
                ));
                continue;
            }

            $merged['published'] += $result['published'];
            $merged['failed'] += $result['failed'];
            $merged['aborted'] = $merged['aborted'] || $result['aborted'];
            $merged['unrecovered'] = $merged['unrecovered'] || $result['unrecovered'];
            $merged['max_outage_s'] = max($merged['max_outage_s'], $result['max_outage_s']);
            $merged['started_at'] = min($merged['started_at'], $result['started_at']);
            $merged['finished_at'] = max($merged['finished_at'], $result['finished_at']);
            $merged['peak_memory'] = max($merged['peak_memory'], $result['peak_memory']);
            array_push($merged['latencies'], ...$result['latencies']);
            array_push($merged['connect_ms'], ...$result['connect_ms']);
            foreach ($result['errors'] as $key => $error) {
                if (!isset($merged['errors'][$key])) {
                    $merged['errors'][$key] = $error;
                    continue;
                }
                $merged['errors'][$key]['count'] += $error['count'];
                $merged['errors'][$key]['first'] = min($merged['errors'][$key]['first'], $error['first']);
                $merged['errors'][$key]['last'] = max($merged['errors'][$key]['last'], $error['last']);
            }
        }

        if ($merged['started_at'] === PHP_FLOAT_MAX) {
            $merged['started_at'] = $merged['finished_at'] = microtime(true);
        }

        return $merged;
    }

    private function consumeAfterPublishing(SymfonyStyle $io, string $runId, int $expected, int $timeout, int $maxErrors): array
    {
        $io->section('Consuming');
        $progress = $io->createProgressBar($expected);
        $result = $this->consume($runId, static fn() => false, static fn() => $expected, $timeout, $maxErrors, $progress);
        $progress->finish();
        $io->newLine(2);

        return $result;
    }

    /**
     * @param \Closure(): bool $publishersRunning
     * @param \Closure(): int $expectedCount only called once publishers are done
     */
    private function consume(string $runId, \Closure $publishersRunning, \Closure $expectedCount, int $idleTimeout, int $maxErrors, ProgressBar $progress): array
    {
        $result = [
            'received' => 0,
            'duplicates' => 0,
            'foreign' => 0,
            'undecodable' => 0,
            'timed_out' => false,
            'aborted' => false,
            'get_latencies' => [],
            'ack_latencies' => [],
            'e2e_latencies' => [],
            'errors' => [],
            'started_at' => microtime(true),
            'last_received_at' => null,
        ];
        $seen = [];
        $errorCount = 0;
        $idleSince = microtime(true);

        while (true) {
            if ($publishersRunning()) {
                $idleSince = microtime(true);
            } else {
                if ($result['received'] >= $expectedCount()) {
                    break;
                }
                if (microtime(true) - $idleSince > $idleTimeout) {
                    $result['timed_out'] = true;
                    break;
                }
            }

            $getStart = hrtime(true);
            try {
                $envelopes = iterator_to_array($this->transport->get(), false);
            } catch (MessageDecodingFailedException) {
                // the receiver already rejected the message
                $result['undecodable']++;
                continue;
            } catch (\Throwable $e) {
                $this->recordError($result['errors'], 'consume', $this->describeThrowable($e));
                if (++$errorCount >= $maxErrors) {
                    $result['aborted'] = true;
                    break;
                }
                usleep(self::ERROR_BACKOFF_MICROSECONDS);
                continue;
            }
            $receivedAt = microtime(true);

            if ($envelopes === []) {
                usleep(self::EMPTY_QUEUE_POLL_MICROSECONDS);
                continue;
            }
            $result['get_latencies'][] = (hrtime(true) - $getStart) / 1e6;

            foreach ($envelopes as $envelope) {
                $message = $envelope->getMessage();
                try {
                    if (!$message instanceof AmqpPerformanceTestMessage || $message->runId !== $runId) {
                        $this->transport->reject($envelope);
                        $result['foreign']++;
                        continue;
                    }

                    $ackStart = hrtime(true);
                    $this->transport->ack($envelope);
                    $result['ack_latencies'][] = (hrtime(true) - $ackStart) / 1e6;
                } catch (\Throwable $e) {
                    $this->recordError($result['errors'], 'ack', $this->describeThrowable($e));
                    if (++$errorCount >= $maxErrors) {
                        $result['aborted'] = true;
                        break 2;
                    }
                    continue;
                }

                $idleSince = $receivedAt;
                if (isset($seen[$message->sequence])) {
                    $result['duplicates']++;
                    continue;
                }
                $seen[$message->sequence] = true;
                $result['received']++;
                $result['last_received_at'] = $receivedAt;
                $result['e2e_latencies'][] = ($receivedAt - $message->sentAt) * 1000;
                $progress->advance();
            }
        }

        return $result;
    }

    private function getConnectionInfo(): array
    {
        // the Messenger transport does not expose its connection, so it is read via reflection for diagnostics only
        try {
            $connection = (new \ReflectionProperty(AmqpTransport::class, 'connection'))->getValue($this->transport);
            $options = (new \ReflectionProperty(Connection::class, 'connectionOptions'))->getValue($connection);
            $amqpConnection = $connection->channel()->getConnection();
        } catch (\Throwable) {
            return [];
        }

        return [
            'host' => sprintf('%s:%d%s', $amqpConnection->getHost(), $amqpConnection->getPort(), $amqpConnection->getVhost()),
            'tls' => $amqpConnection->getCACert() !== null,
            'heartbeat' => $amqpConnection->getHeartbeatInterval(),
            'connect_timeout' => $amqpConnection->getConnectTimeout(),
            'read_timeout' => $amqpConnection->getReadTimeout(),
            'write_timeout' => $amqpConnection->getWriteTimeout(),
            'persistent' => $amqpConnection->isPersistent(),
            'confirm_timeout' => (string)($options['confirm_timeout'] ?? ''),
        ];
    }

    private function renderSettings(SymfonyStyle $io, string $runId, int $messages, int $payloadSize, int $processes, float $rate, bool $concurrent, array $info): void
    {
        $io->definitionList(
            ['Environment' => $this->environment],
            ['Run id' => $runId],
            ['Broker' => $info['host'] ?? 'unknown'],
            ['TLS' => isset($info['tls']) ? ($info['tls'] ? 'yes' : 'no') : 'unknown'],
            ['ext-amqp' => phpversion('amqp') ?: 'unknown'],
            ['Heartbeat' => isset($info['heartbeat']) ? $info['heartbeat'] . ' s' : 'unknown'],
            ['Timeouts (connect/read/write)' => isset($info['connect_timeout']) ? sprintf('%s / %s / %s s', $info['connect_timeout'], $info['read_timeout'], $info['write_timeout']) : 'unknown'],
            ['Persistent connection' => isset($info['persistent']) ? ($info['persistent'] ? 'yes' : 'no') : 'unknown'],
            ['Publisher confirms' => isset($info['confirm_timeout']) ? ($info['confirm_timeout'] === '' ? 'disabled' : 'enabled (' . $info['confirm_timeout'] . ' s)') : 'unknown'],
            new TableSeparator(),
            ['Messages' => $messages],
            ['Payload size' => $this->formatBytes($payloadSize)],
            ['Publisher processes' => $processes],
            ['Target rate' => $rate > 0 ? $rate . ' msg/s' : 'unlimited'],
            ['Mode' => $concurrent ? 'publish and consume concurrently' : 'publish, then consume'],
        );
    }

    private function renderReport(SymfonyStyle $io, array $publish, array $consume, int $payloadSize, float $rate, bool $concurrent, array $info): int
    {
        $publishDuration = max($publish['finished_at'] - $publish['started_at'], 1e-6);
        $publishRate = $publish['published'] / $publishDuration;
        $consumeDuration = $consume['last_received_at'] !== null ? max($consume['last_received_at'] - $consume['started_at'], 1e-6) : null;
        $consumeRate = $consumeDuration !== null ? $consume['received'] / $consumeDuration : 0.0;
        $lost = max(0, $publish['published'] - $consume['received']);
        $unexpected = max(0, $consume['received'] - $publish['published']);

        $io->section('Results');
        $io->table(['Metric', 'Value'], [
            ['Published', sprintf('%d ok / %d failed%s', $publish['published'], $publish['failed'], $publish['aborted'] ? ' (aborted)' : '')],
            ['Publish duration', sprintf('%.2f s', $publishDuration)],
            ['Publish throughput', sprintf('%.1f msg/s (%s/s payload)', $publishRate, $this->formatBytes((int)($publishRate * $payloadSize)))],
            ['Consumed', sprintf('%d%s', $consume['received'], $consume['timed_out'] ? ' (timed out)' : ($consume['aborted'] ? ' (aborted)' : ''))],
            ['Consume throughput (1 consumer)', $consumeDuration !== null ? sprintf('%.1f msg/s', $consumeRate) : '-'],
            ['Lost messages', $lost],
            ['Duplicated messages', $consume['duplicates']],
            ['Foreign/undecodable messages discarded', $consume['foreign'] + $consume['undecodable']],
            ['Longest publish outage', $publish['max_outage_s'] > 0 ? sprintf('%.2f s%s', $publish['max_outage_s'], $publish['unrecovered'] ? ' (not recovered)' : '') : '-'],
            ['Peak memory (publisher)', $this->formatBytes($publish['peak_memory'])],
        ]);

        $io->table(['Latency (ms)', 'Samples', 'Min', 'Avg', 'p50', 'p95', 'p99', 'Max'], [
            $this->latencyRow('Connect (incl. TLS + queue declare)', $publish['connect_ms']),
            $this->latencyRow('Publish', $publish['latencies']),
            $this->latencyRow('Get (basic.get round trip)', $consume['get_latencies']),
            $this->latencyRow('Ack', $consume['ack_latencies']),
            $this->latencyRow($concurrent ? 'End-to-end (publish → consume)' : 'Time in queue (publish → consume)', $consume['e2e_latencies']),
        ]);

        $errors = array_merge($publish['errors'], $consume['errors']);
        if ($errors !== []) {
            $io->table(
                ['Phase', 'Error', 'Count', 'First (s)', 'Last (s)'],
                array_map(fn(array $error) => [
                    $error['phase'],
                    $error['message'],
                    $error['count'],
                    sprintf('%.2f', $error['first'] - $this->testStartedAt),
                    sprintf('%.2f', $error['last'] - $this->testStartedAt),
                ], array_values($errors)),
            );
        }

        $hints = $this->buildHints($publish, $consume, $publishRate, $consumeRate, $rate, $lost, $unexpected, $concurrent, $info);
        if ($hints !== []) {
            $io->section('Findings');
            $io->listing($hints);
        }

        $errorCount = array_sum(array_column($errors, 'count'));
        if ($lost > 0 || $errorCount > 0 || $publish['aborted'] || $consume['aborted']) {
            $io->error(sprintf('AMQP performance test finished with problems: %d lost message(s), %d error(s).', $lost, $errorCount));

            return Command::FAILURE;
        }

        $io->success('AMQP performance test finished without errors.');

        return Command::SUCCESS;
    }

    private function buildHints(array $publish, array $consume, float $publishRate, float $consumeRate, float $targetRate, int $lost, int $unexpected, bool $concurrent, array $info): array
    {
        $hints = [];
        $publishP50 = $this->percentile($publish['latencies'], 50);
        $publishP99 = $this->percentile($publish['latencies'], 99);
        $connectAvg = $publish['connect_ms'] !== [] ? array_sum($publish['connect_ms']) / count($publish['connect_ms']) : 0.0;
        $getP50 = $this->percentile($consume['get_latencies'], 50);

        if ($lost > 0) {
            $hints[] = sprintf('<error>%d message(s) were published without error but never consumed.</error> Without publisher confirms a successful publish only means the bytes were written to the socket.', $lost);
        }
        if ($unexpected > 0) {
            $hints[] = sprintf('%d message(s) arrived although publishing reported an error (e.g. confirm timeout). Retrying such publishes creates duplicates.', $unexpected);
        }
        if ($consume['duplicates'] > 0) {
            $hints[] = sprintf('%d message(s) were delivered more than once (redelivery after failed ack / reconnect). Handlers must be idempotent.', $consume['duplicates']);
        }
        if (($info['confirm_timeout'] ?? null) === '') {
            $hints[] = 'Publisher confirms are disabled: publish latency only measures writing to the socket and broker-side failures go unnoticed. Add e.g. "confirm_timeout=5" to RABBITMQ_DSN (costs latency, prevents silent loss).';
        }
        if (($info['heartbeat'] ?? null) === 0) {
            $hints[] = 'AMQP heartbeats are disabled: dead TCP connections (firewall/NAT/load balancer idle timeouts) are only noticed on the next read/write. Long running workers should set e.g. "heartbeat=60" in RABBITMQ_DSN.';
        }
        if ($publish['max_outage_s'] > 0) {
            $hints[] = sprintf('Publishing failed for up to %.2f s%s. Check the error table for the cause and the broker logs at that time.', $publish['max_outage_s'], $publish['unrecovered'] ? ' and did not recover' : ' before recovering');
        }
        if ($publishP50 !== null && $publishP99 !== null && $publishP99 > 50 && $publishP99 > 10 * $publishP50) {
            $hints[] = sprintf('Publish latency spikes (p50 %.2f ms vs p99 %.2f ms). Typical causes: broker flow control / memory or disk alarms (check "rabbitmq-diagnostics alarms" and blocked connections in the management UI), network jitter or GC pauses.', $publishP50, $publishP99);
        }
        if ($targetRate > 0 && $publishRate < 0.9 * $targetRate) {
            $hints[] = sprintf('Target rate of %.0f msg/s was not reached (%.1f msg/s). Publishers are saturated; increase --processes to find the broker limit.', $targetRate, $publishRate);
        }
        if ($consumeRate > 0 && $consumeRate < 0.8 * $publishRate) {
            $hints[] = sprintf('One consumer drains %.1f msg/s while publishers produce %.1f msg/s. Messenger consumes via basic.get (one round trip per message%s), so under sustained load the queue grows unless more "messenger:consume" processes run.', $consumeRate, $publishRate, $getP50 !== null ? sprintf(', p50 %.2f ms', $getP50) : '');
        }
        if ($connectAvg > 50) {
            $hints[] = sprintf('Opening a connection takes %.0f ms on average. Every PHP-FPM request that dispatches a message opens a new connection, so under high web load this causes connection churn on the broker; keep an eye on connection count/churn in RabbitMQ.', $connectAvg);
        }
        if ($consume['foreign'] + $consume['undecodable'] > 0) {
            $hints[] = sprintf('%d leftover message(s) were discarded from the "%s" queue.', $consume['foreign'] + $consume['undecodable'], self::TRANSPORT_NAME);
        }
        if (!$concurrent) {
            $hints[] = 'Run with --concurrent to measure real end-to-end latency while publishing and consuming at the same time.';
        }

        return $hints;
    }

    private function latencyRow(string $label, array $values): array
    {
        if ($values === []) {
            return [$label, 0, '-', '-', '-', '-', '-', '-'];
        }
        sort($values);

        return [
            $label,
            count($values),
            sprintf('%.2f', $values[0]),
            sprintf('%.2f', array_sum($values) / count($values)),
            sprintf('%.2f', $this->percentile($values, 50, true)),
            sprintf('%.2f', $this->percentile($values, 95, true)),
            sprintf('%.2f', $this->percentile($values, 99, true)),
            sprintf('%.2f', end($values)),
        ];
    }

    private function percentile(array $values, float $percentile, bool $sorted = false): ?float
    {
        if ($values === []) {
            return null;
        }
        if (!$sorted) {
            sort($values);
        }
        $index = (int)max(0, ceil($percentile / 100 * count($values)) - 1);

        return (float)$values[$index];
    }

    private function recordError(array &$errors, string $phase, string $message): void
    {
        $now = microtime(true);
        $key = $phase . '|' . $message;
        $errors[$key] ??= ['phase' => $phase, 'message' => $message, 'count' => 0, 'first' => $now, 'last' => $now];
        $errors[$key]['count']++;
        $errors[$key]['last'] = $now;
    }

    private function describeThrowable(\Throwable $e): string
    {
        $root = $e;
        while ($root->getPrevious() !== null) {
            $root = $root->getPrevious();
        }

        return mb_strimwidth(sprintf('%s: %s', (new \ReflectionClass($root))->getShortName(), $root->getMessage()), 0, 200, '…');
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KiB', 'MiB', 'GiB'];
        $value = (float)$bytes;
        $unit = 0;
        while ($value >= 1024 && $unit < count($units) - 1) {
            $value /= 1024;
            $unit++;
        }

        return $unit === 0 ? $bytes . ' B' : sprintf('%.1f %s', $value, $units[$unit]);
    }
}
