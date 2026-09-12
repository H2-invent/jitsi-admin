<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Reporting extends AbstractExtension
{
    public function getFunctions(): array
    {

        return [
            new TwigFunction('getTotalSpeakingTime', [$this, 'getTotalSpeakingTime']),
        ];
    }

    public function getTotalSpeakingTime(\App\Entity\RoomStatus $roomStatus)
    {
        $time = 0;
        foreach ($roomStatus->getRoomStatusParticipants() as $data) {
            if ($data->getDominantSpeakerTime()) {
                $time += $data->getDominantSpeakerTime();
            }
        }
        return $time;
    }
}
