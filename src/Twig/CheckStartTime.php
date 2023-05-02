<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Entity\User;
use App\Service\StartMeetingService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CheckStartTime extends AbstractExtension
{
    public function __construct(
        private StartMeetingService $startMeetingService,
    )
    {
    }

    public function getFunctions(): array
    {

        return [
            new TwigFunction('isRoomOpen', [$this, 'isRoomOpen']),
        ];
    }

    public function isRoomOpen(Rooms $room, ?User $user)
    {
        return $this->startMeetingService->IsAlloedToEnter($room, $user);
    }
}
