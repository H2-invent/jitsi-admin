<?php

namespace App\MessageHandler;

use App\Message\Provisioner\Enum\Status;
use App\Message\Provisioner\Enum\Type;
use App\Message\Provisioner\ProvisionerStatusMessage;
use App\Repository\ProvisionerRecordRepository;
use App\Service\Provisioner\ProvisionerStatus;
use App\Service\Provisioner\ProvisionerStatusService;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
final class ProvisionerStatusMessageHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ProvisionerStatusService $provisionerStatusService,
        private readonly ProvisionerRecordRepository $provisionerRecordRepository,
    )
    {
    }

    public function __invoke(ProvisionerStatusMessage $message): void
    {
        $provisionerRecord = $this->provisionerRecordRepository->find($message->request_id);
        if ($provisionerRecord === null) {
            throw new UnrecoverableMessageHandlingException("Could not find ProvisionerRecord for id: {$message->request_id}");
        }

        try {
            switch ($message->type) {
                case Type::PROVISION:
                    switch ($message->status) {
                        case Status::FAILED:
                            $this->logger->error("Provisioner status 'failed' for requestId: {$message->request_id}.");
                            $this->provisionerStatusService->failProvision($provisionerRecord);

                            return;

                        case Status::STARTED:
                            $this->logger->info("Provisioner status 'started' for requestId: {$message->request_id}");

                            return;

                        case Status::DONE:
                            $this->logger->info("Provisioner status 'done' for requestId: {$message->request_id}");
                            $this->provisionerStatusService->completeProvision($provisionerRecord, $message);

                            return;
                    }

                case Type::DELETION:
                    switch ($message->status) {
                        case Status::FAILED:
                            $this->logger->info("Deletion status 'failed' for requestId: {$message->request_id}");
                            if ($provisionerRecord->getStatus() === ProvisionerStatus::PROVISION_STUCK) {
                                $this->provisionerStatusService->stuckDeletion($provisionerRecord);
                            } else {
                                $this->provisionerStatusService->failDeletion($provisionerRecord);
                            }

                            return;

                        case Status::STARTED:
                            $this->logger->info("Provisioner deletion status 'started' for requestId: {$message->request_id}");

                            return;

                        case Status::DONE:
                            $this->logger->info("Deletion status 'done' for requestId: {$message->request_id}. Deleting server");
                            $this->provisionerStatusService->completeDeletion($provisionerRecord);

                            return;
                    }
            }
        } catch (LogicException $exception) {
            throw new UnrecoverableMessageHandlingException("Logic Exception: {$exception->getMessage()}", previous: $exception);
        }
    }
}
