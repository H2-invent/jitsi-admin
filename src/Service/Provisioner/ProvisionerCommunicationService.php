<?php
declare(strict_types=1);

namespace App\Service\Provisioner;

use App\Entity\ProvisionerRecord;
use App\Message\Provisioner\Enum\Type;
use App\Message\Provisioner\ProvisionerRequestMessage;
use App\Service\Lobby\DirectSendService;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProvisionerCommunicationService
{
    public const WEBSOCKET_TOPIC_NAME = 'provisioner_wait_cluster/';

    private const DELETION_DELAY_MS = 20 * 1000; // 20 seconds

    public function __construct(
        private readonly DirectSendService $websocketService,
        private readonly MessageBusInterface $messageBus,
        private readonly UrlGeneratorInterface $urlGenerator,
    )
    {
    }

    public function sendProvisionRequest(ProvisionerRecord $record): void
    {
        $provisionMessage = new ProvisionerRequestMessage(
            $record->getId(),
            Type::PROVISION,
        );
        $this->messageBus->dispatch($provisionMessage);
    }

    public function sendDeleteRequest(ProvisionerRecord $record): void
    {
        $deletionMessage = new ProvisionerRequestMessage(
            $record->getId(),
            Type::DELETION,
        );
        // we delay deletions because we ran into problems of servers being killed too early and getting 502 in the iframe
        $delayStamp = new DelayStamp(self::DELETION_DELAY_MS);
        $envelope = new Envelope($deletionMessage, [$delayStamp]);

        $this->messageBus->dispatch($envelope);
    }

    public function sendWebsocketRedirect(ProvisionerRecord $record): void
    {
        $room = $record->getRoom();
        $redirectUrl = $this->urlGenerator->generate(
            'room_join',
            [
                'type' => 'b',
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
}
