<?php

// src/Twig/AppExtension.php
namespace App\Twig;

class Reporting
{
    #[\Twig\Attribute\AsTwigFunction(name: 'getTotalSpeakingTime')]
    public function getTotalSpeakingTime(\App\Entity\RoomStatus $roomStatus): int
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
