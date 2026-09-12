<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Entity\User;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SipCallIn extends AbstractExtension
{
    public function getFunctions(): array
    {

        return [
            new TwigFunction('sipPinFromRoomAndUser', [$this, 'sipPinFromRoomAndUser'])
        ];
    }

    public function sipPinFromRoomAndUser(Rooms $rooms, User $user)
    {
        foreach ($user->getCallerIds() as $data) {
            if ($data->getRoom() === $rooms) {
                return $data;
            }
        }
        return null;
    }
}
