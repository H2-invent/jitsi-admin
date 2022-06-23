<?php

namespace App\Service;

use App\Entity\Rooms;
use App\Entity\User;

class CheckLobbyPermissionService
{

    public function checkPermissions(Rooms $room, ?User $user)
    {
        if ($room->getModerator() === $user) {
            return true;
        }
        if ($user->getPermissionForRoom($room)->getLobbyModerator() === true) {
            return true;
        }
        return false;
    }
}