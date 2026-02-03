<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Rooms;
use App\Message\ProvisionNewInstanceMessage;
use Symfony\Component\Messenger\MessageBusInterface;

class ProvisionerService
{
    public function __construct(
        private MessageBusInterface $messageBus,
    )
    {
    }

    public function provisionNewInstanceForRoom(Rooms $room): void
    {
        $this->saveOriginalServer($room);
        $this->sendMessage($room);
    }

    private function saveOriginalServer(Rooms $room): void
    {
        if ($room->getOriginalServer() === null) {
            $room->setOriginalServer($room->getServer());
        }
    }

    private function sendMessage(Rooms $room): void
    {
        $provisionMessage = ProvisionNewInstanceMessage::fromRoomEntity($room);
        $this->messageBus->dispatch($provisionMessage);
    }
}
