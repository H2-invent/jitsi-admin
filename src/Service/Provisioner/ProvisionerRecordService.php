<?php
declare(strict_types=1);

namespace App\Service\Provisioner;

use App\Entity\ProvisionerRecord;
use App\Entity\Rooms;
use App\Repository\ProvisionerRecordRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProvisionerRecordService
{
    private const MAX_RETRY_ATTEMPTS = 10;

    public function __construct(
        private readonly ProvisionerRecordRepository $repository,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function getRequestedProvisioning(Rooms $room): ?ProvisionerRecord
    {
        return $this->repository->findOneBy([
            'room' => $room,
            'status' => ProvisionerStatus::PROVISION_REQUESTED,
        ]);
    }

    public function getRunningProvisioning(Rooms $room): ?ProvisionerRecord
    {
        return $this->repository->findOneBy([
            'room' => $room,
            'status' => ProvisionerStatus::PROVISION_DONE,
        ]);
    }

    /**
     * @return ProvisionerRecord[]
     */
    public function getDeletableProvisionings(Rooms $room): array
    {
        return $this->repository->findByStatuses($room, [
            ProvisionerStatus::PROVISION_DONE,
            ProvisionerStatus::PROVISION_REQUESTED,
        ]);
    }

    public function createNewProvisioningRecord(Rooms $room, ?int $retryAttempts = 0): ProvisionerRecord
    {
        $record = (new ProvisionerRecord())
            ->setRoom($room)
            ->setStatus(ProvisionerStatus::IDLE)
            ->setRetryAttempts($retryAttempts)
        ;

        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return $record;
    }

    public function shouldAttemptRetry(ProvisionerRecord $record): bool
    {
        return $record->getRetryAttempts() < self::MAX_RETRY_ATTEMPTS;
    }

    public function createNewRetryRecord(ProvisionerRecord $record): ProvisionerRecord
    {
        return $this->createNewProvisioningRecord($record->getRoom(), $record->getRetryAttempts() + 1);
    }
}

