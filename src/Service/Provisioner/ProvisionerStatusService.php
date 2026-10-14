<?php
declare(strict_types=1);

namespace App\Service\Provisioner;

use App\Entity\ProvisionerRecord;
use App\Message\Provisioner\ProvisionerStatusMessage;
use App\Service\Provisioner\ProvisionerStatus as Status;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

class ProvisionerStatusService
{
    private array $allowedTransitions = [
        Status::IDLE->value => [
            Status::PROVISION_REQUESTED,
        ],
        Status::PROVISION_REQUESTED->value => [
            Status::PROVISION_DONE,
            Status::PROVISION_FAILED,
        ],
        Status::PROVISION_DONE->value => [
            Status::DELETION_REQUESTED,
        ],
        Status::DELETION_REQUESTED->value => [
            Status::DELETION_DONE,
            Status::DELETION_FAILED,
        ],
    ];


    public function __construct(
        private readonly ProvisionerCommunicationService $communicationService,
        private readonly ProvisionerPersistenceService $persistenceService,
        private readonly ProvisionerRecordService $recordService,
        private readonly EntityManagerInterface $entityManager,
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

    public function requestDeletion(ProvisionerRecord $record): void
    {
        $this->assertTransition($record->getStatus(), Status::DELETION_REQUESTED);
        $this->deletionRequest($record);
    }

    public function completeDeletion(ProvisionerRecord $record): void
    {
        $this->assertTransition($record->getStatus(), Status::DELETION_DONE);
        $this->deletionDone($record);
    }

    public function failDeletion(ProvisionerRecord $record): void
    {
        $this->assertTransition($record->getStatus(), Status::DELETION_FAILED);
        $this->deletionFailed($record);
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

        $this->persistenceService->saveNewServer($record, $statusMessage);
        $this->communicationService->sendWebsocketRedirect($record);
        $this->setRecordStatus($record, Status::PROVISION_DONE);
    }

    // TRANSITION PROVISION_REQUESTED -> PROVISION_FAILED
    private function provisionFailed(ProvisionerRecord $record): void
    {
        $this->setRecordStatus($record, Status::PROVISION_FAILED);

        if ($this->recordService->shouldAttemptRetry($record)) {
            $retryRecord = $this->recordService->createNewRetryRecord($record);
            $this->requestProvision($retryRecord);
        }
    }

    // TRANSITION PROVISION_DONE -> DELETION_REQUESTED
    private function deletionRequest(ProvisionerRecord $record): void
    {
        $room = $record->getRoom();

        $this->communicationService->sendDeleteRequest($record);
        $this->persistenceService->restoreOriginalServer($room);
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
        // ignore for now, this should be picked up by cronjob to restart deletion
        $this->setRecordStatus($record, Status::DELETION_FAILED);
    }

    private function setRecordStatus(ProvisionerRecord $record, Status $status): void
    {
        $record->setStatus($status);

        $this->entityManager->persist($record);
        $this->entityManager->flush();
    }
}
