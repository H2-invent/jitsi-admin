<?php

namespace App\Twig\Runtime;

use App\Entity\Rooms;
use App\Entity\User;
use App\Repository\RecordingRepository;

use Twig\Extension\RuntimeExtensionInterface;

class LivekitRecordingRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private RecordingRepository $recordingRepository
    )
    {

    }

    public function getRecordingForRoomAndUser(?User $user, ?Rooms $room)
    {
        if ($room && $user){
            return $this->recordingRepository->findOneBy(['room' => $room, 'user' => $user]);
        }
        return null;

    }
}
