<?php
declare(strict_types=1);

namespace App\Service\Provisioner;

use App\Entity\ProvisionerRecord;
use App\Entity\Rooms;
use App\Repository\ProvisionerRecordRepository;
use App\Repository\RoomsRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Use this mostly as a facade - if we need some glue between the other services for outside capabilities
 */
class ProvisionerService
{
    public function __construct(
        private readonly ProvisionerCommunicationService $communicationService,
        private readonly ProvisionerPersistenceService $persistenceService,
        private readonly ProvisionerRecordService $recordService,
        private readonly ProvisionerStatusService $statusService,
        #[Autowire(param: 'provisioner.schedule.minutes_threshold')]
        private readonly int $scheduleMinutesTreshold,
        private readonly RoomsRepository $roomsRepository,
        private readonly ProvisionerRecordRepository $provisionerRecordRepository,
    )
    {
    }

    public function startProvision(Rooms $room): ProvisionerRecord
    {
        $requestedProvisioning = $this->recordService->getRequestedProvisioning($room);
        if ($requestedProvisioning !== null) {
            return $requestedProvisioning;
        }

        $newProvisioning = $this->recordService->createNewProvisioningRecord($room);
        $this->statusService->requestProvision($newProvisioning);

        return $newProvisioning;
    }

    public function deleteProvision(Rooms $room): void
    {
        $deletableProvisionings = $this->recordService->getDeletableProvisionings($room);
        foreach ($deletableProvisionings as $deletableProvisioning) {
            $this->statusService->requestDeletion($deletableProvisioning);
        }
    }

    public function provisionServerForRoomsStartingIn(?int $minutes = null): int
    {
        $minutes ??= $this->scheduleMinutesTreshold;

        $rooms = $this->roomsRepository->findRoomsToProvisionInXMinutes($minutes);
        foreach ($rooms as $room) {
            $this->startProvision($room);
        }

        return count($rooms);
    }

    public function cleanupFinishedProvisions(): int
    {
        $provisioningRecords = $this->provisionerRecordRepository->findProvisioningRecordsWhoseServerCanBeDeleted($this->scheduleMinutesTreshold);
        foreach ($provisioningRecords as $provisioningRecord) {
            $this->statusService->requestDeletion($provisioningRecord);
        }

        return count($provisioningRecords);
    }

    public function cleanupStuckProvisions(): int
    {
        $stuckProvisions = $this->provisionerRecordRepository->findStuckProvisions();
        foreach ($stuckProvisions as $provisioningRecord) {
            $this->statusService->stuckProvision($provisioningRecord);
        }

        return count($stuckProvisions);
    }

    public function cleanupStuckDeletions(): int
    {
        $stuckDeletions = $this->provisionerRecordRepository->findStuckDeletions();
        foreach ($stuckDeletions as $provisioningRecord) {
            $this->statusService->failDeletion($provisioningRecord);
        }

        return count($stuckDeletions);
    }
}
