<?php


namespace App;


use App\Entity\Rooms;
use App\Entity\User;

class UtilsHelper
{
    public static function slugify($urlString)
    {
        $slug = preg_replace("/[^a-zA-Z0-9 ]/", "", strtolower($urlString));
        $slug = preg_replace("/[ ]/", "_", $slug);
        return $slug;
    }

    public static function slugifywithDot($urlString)
    {
        $slug = preg_replace("/[^a-zA-Z0-9. ]/", "", strtolower($urlString));
        $slug = preg_replace("/[ ]/", "_", $slug);
        return $slug;
    }

    public static function readable_random_string($length = 6)
    {
        $string = '';
        $vowels = array("a", "e", "i", "o", "u");
        $consonants = array(
            'b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm',
            'n', 'p', 'r', 's', 't', 'v', 'w', 'x', 'y', 'z'
        );

        $max = $length / 2;
        for ($i = 1; $i <= $max; $i++) {
            $string .= $consonants[rand(0, 19)];
            $string .= $vowels[rand(0, 4)];
        }

        return $string;
    }

    public static function isAllowedToOrganizeRoom(?User $user, Rooms $room): bool
    {
        if (!$user) {
            return false;
        }
        if ($user === $room->getCreator() || $user === $room->getModerator() || UtilsHelper::roomGeneratedByOtherDeputy($room,$user)) {
            return true;
        }
        return false;
    }
    public static function isRoomReadOnly(Rooms $rooms, User $user):bool{
        if (
            $user === $rooms->getModerator() ||
            $user === $rooms->getCreator() ||
            $rooms->getUser()->contains($user)||
            UtilsHelper::roomGeneratedByOtherDeputy($rooms,$user)
        ) {
            return false;
        }
        return true;

    }

    public static function roomGeneratedByOtherDeputy(Rooms $rooms,User $user):bool{
        if (in_array($rooms->getCreator(), $rooms->getModerator()->getDeputy()->toArray()) && in_array($user,$rooms->getModerator()->getDeputy()->toArray())){
            return true;
        }else{
            return false;
        }
    }

    public static function isAllowedToOrganizeLobby(?User $user, Rooms $room): bool
    {
        if (!$user){
            return false;
        }
        if ($user === $room->getModerator() || $user->getPermissionForRoom($room)->getLobbyModerator()) {
            return true;
        }
        return false;
    }

    public static function hasModeratorRights(?User $user, Rooms $room): bool
    {
        if (!$user){
            return false;
        }
        if ($user === $room->getModerator() || $user->getPermissionForRoom($room)->getLobbyModerator()) {
            return true;
        }
        return false;
    }


}