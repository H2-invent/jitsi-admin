<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Rooms;
use App\Message\ProvisionerRequest\RequestType;
use App\Message\ProvisionerRequestMessage;
use App\Message\ProvisionerStatusMessage;
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
    )
    {
    }

    public function provisionNewServerForRoom(Rooms $room): void
    {
        $this->saveOriginalServer($room);
        $this->sendProvisionerRequest($room);
    }

    public function saveNewServerAndRedirect(Rooms $room, ProvisionerStatusMessage $statusMessage): void
    {
        $this->saveNewServer($room, $statusMessage);
        $this->sendWebsocketRedirect($room);
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

    private function sendProvisionerRequest(Rooms $room): void
    {
        $provisionMessage = new ProvisionerRequestMessage(
            $room->getUidReal(),
            RequestType::PROVISION,
        );
        $this->messageBus->dispatch($provisionMessage);
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
