<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Rooms;
use App\Message\ProvisionerRequest\RequestType;
use App\Message\ProvisionerRequestMessage;
use App\Message\ProvisionerStatusMessage;
use App\Repository\RoomsRepository;
use App\Service\Lobby\DirectSendService;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProvisionerService
{
    public const WEBSOCKET_TOPIC_NAME = 'provisioner_wait_cluster/';

    public function __construct(
        private DirectSendService $websocketService,
        private MessageBusInterface $messageBus,
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager,
        private ServerService $serverService,
        private RoomsRepository $roomsRepository,
    )
    {
    }

    public function provisionNewServerForRoom(Rooms $room): void
    {
        $this->saveOriginalServer($room);
        $this->sendProvisionRequest($room);
    }

    public function saveNewServerAndRedirect(Rooms $room, ProvisionerStatusMessage $statusMessage): void
    {
        $this->saveNewServer($room, $statusMessage);
        $this->sendWebsocketRedirect($room);
    }

    public function cleanupUnusedProvisionedServers(): int
    {
        $rooms = $this->roomsRepository->findRoomsWhoseProvisionedServerCanBeDeleted();
        foreach ($rooms as $room) {
            $this->sendDeleteRequest($room);
        }

        return count($rooms);
    }

    public function removeServerAndRestoreOriginal(Rooms $room): void
    {
        if ($room->getServer() === null || $room->getOriginalServer() === null) {
            throw new RuntimeException("Room server or original server not set. Can not continue deletion. Room ID: {$room->getId()}");
        }

        $server = $room->getServer();
        $room->setServer($room->getOriginalServer());
        $room->setOriginalServer(null);

        $this->entityManager->persist($room);
        $this->entityManager->remove($server);
        $this->entityManager->flush();
    }

    private function saveOriginalServer(Rooms $room): void
    {
        $room->setOriginalServer($room->getServer());

        $this->entityManager->persist($room);
        $this->entityManager->flush();
    }

    private function sendProvisionRequest(Rooms $room): void
    {
        $provisionMessage = new ProvisionerRequestMessage(
            $room->getUidReal(),
            RequestType::PROVISION,
        );
        $this->messageBus->dispatch($provisionMessage);
    }

    private function sendDeleteRequest(Rooms $room): void
    {
        $deletionMessage = new ProvisionerRequestMessage(
            $room->getUidReal(),
            RequestType::DELETION,
        );
        $this->messageBus->dispatch($deletionMessage);
    }

    public function sendWebsocketRedirect(Rooms $room): void
    {
        $redirectUrl = $this->urlGenerator->generate(
            'room_join',
            [
                't' => 'b',
                'room' => $room->getId()
            ],
            UrlGeneratorInterface::ABSOLUTE_PATH,
        );
        $this->websocketService->sendRedirectLocal(
            self::WEBSOCKET_TOPIC_NAME . $room->getUidReal(),
            $redirectUrl,
            0
        );
    }

    private function saveNewServer(Rooms $room, ProvisionerStatusMessage $statusMessage): void
    {
        $newServer = $this->serverService->cloneServerForAutoscaling(
            $room->getServer(),
            $statusMessage->url,
            $statusMessage->name,
            $statusMessage->app_id,
            $statusMessage->app_secret,
        );

        $room->setServer($newServer);
        $this->entityManager->persist($room);
        $this->entityManager->flush();
    }
}
