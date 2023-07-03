<?php

namespace App\Service;

use App\Entity\Rooms;
use App\Entity\User;
use App\UtilsHelper;

class CheckLobbyPermissionService
{
    public function checkPermissions(Rooms $room, ?User $user)
    {
        return UtilsHelper::isAllowedToOrganizeLobby($user, $room);
    }
}
