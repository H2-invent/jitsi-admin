<?php

namespace App\Service;

use App\Entity\User;

class TimeZoneService
{
    public static function getTimeZone(?User $user): ?\DateTimeZone
    {
        $timezone = null;
        if ($user && $user->getTimeZone()) {
            $timezone = new \DateTimeZone($user->getTimeZone());
        }
        return $timezone;
    }
}
