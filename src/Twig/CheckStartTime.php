<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Entity\User;
use App\Service\StartMeetingService;

class CheckStartTime
{
    public function __construct(
        private readonly StartMeetingService $startMeetingService,
    ) {
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'isRoomOpen')]
    public function isRoomOpen(Rooms $room, ?User $user): ?string
    {
        return $this->startMeetingService->isAllowedToEnter($room, $user);
    }
}
