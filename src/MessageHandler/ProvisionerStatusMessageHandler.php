<?php

namespace App\MessageHandler;

use App\Message\ProvisionerStatus\Status;
use App\Message\ProvisionerStatusMessage;
use App\Repository\RoomsRepository;
use App\Service\ProvisionerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ProvisionerStatusMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private ProvisionerService $provisionerService,
        private RoomsRepository $roomsRepository,
    )
    {
    }

    public function __invoke(ProvisionerStatusMessage $message): void
    {
        switch ($message->status) {
            case Status::FAILED:
                $this->logger->error("Provisioner status 'failed' for roomId: {$message->room_id}. Retrying");
                $room = $this->roomsRepository->findOneBy(['uidReal' => $message->room_id]);
                if ($room === null) {
                    $errorMessage = "Could not retry provisioning for roomId: {$message->room_id} as room could not be found";
                    $this->logger->critical($errorMessage);
                    throw new \RuntimeException($errorMessage);
                }
                $this->provisionerService->provisionNewServerForRoom($room);

                return;

            case Status::STARTED:
                $this->logger->info("Provisioner status 'started' for roomId: {$message->room_id}");

                return;

            case Status::READY:
                $this->logger->info("Provisioner status 'ready' for roomId: {$message->room_id}");
                $room = $this->roomsRepository->findOneBy(['uidReal' => $message->room_id]);
                $this->provisionerService->saveNewServerAndRedirect($room, $message);

                return;

            case Status::DELETING:
                $this->logger->info("Provisioner status 'deleting' for roomId: {$message->room_id}");

                return;
            case Status::DELETED:
                $this->logger->info("Provisioner status 'deleted' for roomId: {$message->room_id}. Deleting server");
                $room = $this->roomsRepository->findOneBy(['uidReal' => $message->room_id]);
                $this->provisionerService->removeServerAndRestoreOriginal($room);

                return;
        }
    }
}
