<?php

namespace App\Service;

use App\Entity\Rooms;
use App\Service\webhook\RoomStatusFrontendService;

class CheckMaxUserService
{


    public function __construct(
        private RoomStatusFrontendService $roomStatusFrontendService,

    )
    {

    }

    function isAllowedToEnter(Rooms $rooms): bool
    {
        $var = $rooms->getMaxUser();
        if ($rooms->getMaxUser() === null){
            return  true;
        }
        $userInRoom  = $this->roomStatusFrontendService->numberOfOccupants($rooms);
        if (count($userInRoom) < $rooms->getMaxUser()){
            return true;
        }
        return false;
    }

}