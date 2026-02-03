<?php

namespace App\Message;

use App\Entity\Rooms;

final class ProvisionNewInstanceMessage
{
    public function __construct(
        public readonly int $roomId,
        public readonly int $serverId,
    )
    {
    }

    public static function fromRoomEntity(Rooms $room): self
    {
        return new self(
            $room->getId(),
            $room->getServer()->getId(),
        );
    }
}
