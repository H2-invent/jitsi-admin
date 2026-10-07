<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\CallerId;
use App\Entity\Rooms;
use App\Entity\User;

class SipCallIn
{
    #[\Twig\Attribute\AsTwigFunction(name: 'sipPinFromRoomAndUser')]
    public function sipPinFromRoomAndUser(Rooms $rooms, User $user): ?CallerId
    {
        foreach ($user->getCallerIds() as $data) {
            if ($data->getRoom() === $rooms) {
                return $data;
            }
        }
        return null;
    }
}
