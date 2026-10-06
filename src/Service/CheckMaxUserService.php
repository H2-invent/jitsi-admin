<?php

namespace App\Service;

use App\Entity\Rooms;
use App\Service\Webhook\RoomStatusFrontendService;

class CheckMaxUserService
{


    public function __construct(
        private readonly RoomStatusFrontendService $roomStatusFrontendService,

    ) {
    }

    public function isAllowedToEnter(Rooms $rooms): bool
    {
        if ($rooms->getMaxUser() === null) {
            return true;
        }

        $userInRoom = $this->roomStatusFrontendService->numberOfOccupants($rooms);
        if (count($userInRoom) < $rooms->getMaxUser()) {
            return true;
        }

        return false;
    }

}