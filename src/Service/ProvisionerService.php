<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Rooms;
use App\Message\ProvisionerRequest\RequestType;
use App\Message\ProvisionerRequestMessage;
use App\Message\ProvisionerStatusMessage;
use App\Repository\ServerRepository;
use App\Service\Lobby\DirectSendService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProvisionerService
{
    public const WEBSOCKET_TOPIC_NAME = 'provisioner_wait_cluster/';

    public function __construct(
        private DirectSendService $websocketService,
        private MessageBusInterface $messageBus,
        private UrlGeneratorInterface $urlGenerator,
        private ServerRepository $serverRepository,
        private EntityManagerInterface $entityManager,
    )
    {
    }

    public function provisionNewServerForRoom(Rooms $room): void
    {
        $this->saveOriginalServer($room);
        $this->sendMessage($room);
    }

    public function saveNewServerAndRedirect(Rooms $room, ProvisionerStatusMessage $statusMessage): void
    {
        $this->saveNewServer($room, $statusMessage);
        $this->sendWebsocketRedirect($room);
    }

    private function saveOriginalServer(Rooms $room): void
    {
        if ($room->getOriginalServer() === null) {
            $room->setOriginalServer($room->getServer());
        }
    }

    private function sendMessage(Rooms $room): void
    {
        $provisionMessage = new ProvisionerRequestMessage(
            $room->getUidReal(),
            RequestType::PROVISION,
        );
        $this->messageBus->dispatch($provisionMessage);
    }

    private function sendWebsocketRedirect(Rooms $room): void
    {
        $redirectUrl = $this->urlGenerator->generate(
            'room_join',
            [
                't' => 'b',
                'room' => $room->getId()
            ],
            UrlGeneratorInterface::RELATIVE_PATH
        );
        $this->websocketService->sendRedirect(
            self::WEBSOCKET_TOPIC_NAME . $room->getUidReal(),
            $redirectUrl,
        );
    }

    private function saveNewServer(Rooms $room, ProvisionerStatusMessage $statusMessage): void
    {
        $newServer = clone $room->getServer();
        $newServer
            ->setUrl($statusMessage->url)
            ->setServerName($statusMessage->name)
            ->setAppId($statusMessage->app_id)
            ->setAppSecret($statusMessage->app_secret)
            ->setUpdatedAt(new \DateTime())
            ->setSlug(urlencode($statusMessage->url))
        ;
        $newServer->getUser()->clear();
        $newServer->setAdministrator(null);
        $this->entityManager->persist($newServer);

        $room->setServer($newServer);
        $this->entityManager->persist($room);

        $this->entityManager->flush();
    }
}
