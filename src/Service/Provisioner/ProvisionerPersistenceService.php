<?php
declare(strict_types=1);

namespace App\Service\Provisioner;

use App\Entity\ProvisionerRecord;
use App\Entity\Rooms;
use App\Message\Provisioner\ProvisionerStatusMessage;
use App\Service\ServerService;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

class ProvisionerPersistenceService
{
    public function __construct(
        private readonly ServerService $serverService,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function restoreOriginalServer(Rooms $room): void
    {
        if ($room->getServer() === null || $room->getOriginalServer() === null) {
            throw new RuntimeException("Room server or original server not set. Can not continue deletion. Room ID: {$room->getId()}");
        }

        $server = $room->getServer();
        $room->setServer($room->getOriginalServer());
        $room->setOriginalServer(null);

        $this->entityManager->persist($room);
        $this->entityManager->flush();
    }

    public function removeServerEntity(ProvisionerRecord $record): void
    {
        $this->entityManager->remove($record->getProvisionedServer());
        $record->setProvisionedServer(null);

        $this->entityManager->persist($record);
        $this->entityManager->flush();
    }

    public function saveOriginalServer(Rooms $room): void
    {
        $room->setOriginalServer($room->getServer());

        $this->entityManager->persist($room);
        $this->entityManager->flush();
    }

    public function saveNewServer(ProvisionerRecord $record, ProvisionerStatusMessage $statusMessage): void
    {
        $room = $record->getRoom();

        $newServer = $this->serverService->cloneServerForAutoscaling(
            $room->getServer(),
            $statusMessage->url,
            $statusMessage->name,
            $statusMessage->app_id,
            $statusMessage->app_secret,
        );

        $room->setServer($newServer);
        $record->setProvisionedServer($newServer);

        $this->entityManager->persist($room);
        $this->entityManager->persist($record);
        $this->entityManager->flush();
    }
}
