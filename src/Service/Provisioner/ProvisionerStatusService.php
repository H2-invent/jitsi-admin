<?php
declare(strict_types=1);

namespace App\Service\Provisioner;

use App\Entity\ProvisionerRecord;
use App\Message\Provisioner\ProvisionerStatusMessage;
use App\Service\Provisioner\ProvisionerStatus as Status;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\Log\LoggerInterface;

class ProvisionerStatusService
{
    private array $allowedTransitions = [
        Status::IDLE->value => [
            Status::PROVISION_REQUESTED,
        ],
        Status::PROVISION_REQUESTED->value => [
            Status::PROVISION_DONE,
            Status::PROVISION_FAILED,
            Status::PROVISION_STUCK,
            Status::DELETION_REQUESTED,
        ],
        Status::PROVISION_STUCK->value => [
            Status::DELETION_STUCK,
        ],
        Status::PROVISION_DONE->value => [
            Status::DELETION_REQUESTED,
        ],
        Status::DELETION_REQUESTED->value => [
            Status::DELETION_DONE,
            Status::DELETION_FAILED,
        ],
        Status::DELETION_FAILED->value => [
            Status::DELETION_REQUESTED,
        ],
    ];


    public function __construct(
        private readonly ProvisionerCommunicationService $communicationService,
        private readonly ProvisionerPersistenceService $persistenceService,
        private readonly ProvisionerRecordService $recordService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function requestProvision(ProvisionerRecord $record): void
    {
        $this->assertTransition($record->getStatus(), Status::PROVISION_REQUESTED);
        $this->provisionRequest($record);
    }

    public function completeProvision(ProvisionerRecord $record, ProvisionerStatusMessage $statusMessage): void
    {
        $this->assertTransition($record->getStatus(), Status::PROVISION_DONE);
        $this->provisionDone($record, $statusMessage);
    }

    public function failProvision(ProvisionerRecord $record): void
    {
        $this->assertTransition($record->getStatus(), Status::PROVISION_FAILED);
        $this->provisionFailed($record);
    }

    public function stuckProvision(ProvisionerRecord $record): void
    {
        $this->assertTransition($record->getStatus(), Status::PROVISION_STUCK);
        $this->provisionStuck($record);
    }

    public function requestDeletion(ProvisionerRecord $record): void
    {
        $this->assertTransition($record->getStatus(), Status::DELETION_REQUESTED);

        switch ($record->getStatus()) {
            case Status::PROVISION_DONE:
                $this->deletionRequest($record);
                break;

            case Status::PROVISION_REQUESTED:
                $this->earlyDeletionRequest($record);
                break;

            case Status::PROVISION_FAILED:
                $this->retryDeletionRequest($record);
                break;

            default:
                throw new LogicException('Unimplemented transition.');
        }
    }

    public function completeDeletion(ProvisionerRecord $record): void
    {
        $this->assertTransition($record->getStatus(), Status::DELETION_DONE);
        $this->deletionDone($record);
    }

    public function failDeletion(ProvisionerRecord $record): void
    {
        switch ($record->getStatus()) {
            case Status::DELETION_REQUESTED:
                $this->assertTransition($record->getStatus(), Status::DELETION_FAILED);
                $this->deletionFailed($record);

                break;

            case Status::PROVISION_STUCK:
                $this->assertTransition($record->getStatus(), Status::DELETION_STUCK);
                $this->deletionStuck($record);
                break;

            default:
                throw new LogicException('Unimplemented transition.');
        }
    }

    private function assertTransition(Status $currentStatus, Status $nextStatus): void
    {
        if (!in_array($nextStatus, $this->allowedTransitions[$currentStatus->value] ?? [], true)) {
            throw new LogicException(
                "Invalid transition: {$currentStatus->value} -> {$nextStatus->value}"
            );
        }
    }

    // TRANSITION IDLE -> PROVISION_REQUESTED
    private function provisionRequest(ProvisionerRecord $record): void
    {
        $room = $record->getRoom();

        $this->persistenceService->saveOriginalServer($room);
        $this->communicationService->sendProvisionRequest($record);
        $this->setRecordStatus($record, Status::PROVISION_REQUESTED);
    }

    // TRANSITION PROVISION_REQUESTED -> PROVISION_DONE
    private function provisionDone(ProvisionerRecord $record, ProvisionerStatusMessage $statusMessage): void
    {
        $room = $record->getRoom();
        $runningProvisioning = $this->recordService->getRunningProvisioning($room);
        if ($runningProvisioning !== null) {
            // this might happen for two simultaneous provision requests (unlikely, but possible)
            // so just delete the slower ones
            $this->requestDeletion($record);

            return;
        }

        $this->persistenceService->saveNewServer($record, $statusMessage);
        $this->communicationService->sendWebsocketRedirect($record);
        $this->setRecordStatus($record, Status::PROVISION_DONE);
    }

    // TRANSITION PROVISION_REQUESTED -> PROVISION_FAILED
    private function provisionFailed(ProvisionerRecord $record): void
    {
        $this->setRecordStatus($record, Status::PROVISION_FAILED);

        if (!$this->recordService->shouldAttemptRetry($record)) {
            $this->logger->critical("Stopped retrying provisionings for record_id: {$record->getId()}");
            return;
        }
        $retryRecord = $this->recordService->createNewProvisionRetryRecord($record);
        $this->requestProvision($retryRecord);
    }

    // TRANSITION PROVISION_REQUESTED -> PROVISION_STUCK
    private function provisionStuck(ProvisionerRecord $record): void
    {
        // clean up possibly orphaned remote server, just send a delete for current request and retry with a new request
        $this->setRecordStatus($record, Status::PROVISION_STUCK);
        $this->communicationService->sendDeleteRequest($record);

        if (!$this->recordService->shouldAttemptRetry($record)) {
            $this->logger->critical("Stopped retrying provisionings for record_id: {$record->getId()}");
            return;
        }
        $retryRecord = $this->recordService->createNewProvisionRetryRecord($record);
        $this->requestProvision($retryRecord);
    }

    // TRANSITION PROVISION_DONE -> DELETION_REQUESTED
    private function deletionRequest(ProvisionerRecord $record): void
    {
        $room = $record->getRoom();

        $this->resetRetryCounter($record);
        $this->communicationService->sendDeleteRequest($record);
        $this->persistenceService->restoreOriginalServer($room);
        $this->setRecordStatus($record, Status::DELETION_REQUESTED);
    }

    // TRANSITION PROVISION_REQUESTED -> DELETION_REQUESTED
    private function earlyDeletionRequest(ProvisionerRecord $record): void
    {
        $this->resetRetryCounter($record);
        $this->communicationService->sendDeleteRequest($record);
        $this->setRecordStatus($record, Status::DELETION_REQUESTED);
    }

    // TRANSITION PROVISION_FAILED -> DELETION_REQUESTED
    private function retryDeletionRequest(ProvisionerRecord $record): void
    {
        $this->communicationService->sendDeleteRequest($record);
        $this->setRecordStatus($record, Status::DELETION_REQUESTED);
    }

    // TRANSITION DELETION_REQUESTED -> DELETION_DONE
    private function deletionDone(ProvisionerRecord $record): void
    {
        $this->persistenceService->removeServerEntity($record);
        $this->setRecordStatus($record, Status::DELETION_DONE);
    }

    // TRANSITION DELETION_REQUESTED -> DELETION_FAILED
    private function deletionFailed(ProvisionerRecord $record): void
    {
        $this->setRecordStatus($record, Status::DELETION_FAILED);

        if (!$this->recordService->shouldAttemptRetry($record)) {
            $this->logger->critical("Stopped retrying provisionings for record_id: {$record->getId()}");
            return;
        }

        $this->incrementRetryCounter($record);
        $this->requestDeletion($record);
    }

    // TRANSITION PROVISION_STUCK -> DELETION_STUCK
    private function deletionStuck(ProvisionerRecord $record): void
    {
        // nothing to do here, we don't retry regardless because a deletion failure is expected
        $this->setRecordStatus($record, Status::DELETION_STUCK);
    }

    private function resetRetryCounter(ProvisionerRecord $record): void
    {
        $record->setRetryAttempts(0);

        $this->entityManager->persist($record);
        $this->entityManager->flush();
    }

    private function incrementRetryCounter(ProvisionerRecord $record): void
    {
        $record->setRetryAttempts($record->getRetryAttempts() + 1);

        $this->entityManager->persist($record);
        $this->entityManager->flush();
    }

    private function setRecordStatus(ProvisionerRecord $record, Status $status): void
    {
        $record->setStatus($status);

        $this->entityManager->persist($record);
        $this->entityManager->flush();
    }
}
