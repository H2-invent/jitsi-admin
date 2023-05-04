<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\MyUser;
use App\Entity\User;
use App\Service\MessageService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

use function GuzzleHttp\Psr7\str;

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
