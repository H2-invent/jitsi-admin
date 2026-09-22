<?php

namespace App\Twig\Runtime;

use App\Entity\Recording;
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

    /**
     * @param User|null $user
     * @param Rooms|null $room
     * @return Recording|null
     */
    public function getRecordingForRoomAndUser(?User $user, ?Rooms $room): ?Recording
    {
        if ($room && $user){
            return $this->recordingRepository->findOneBy(['room' => $room, 'user' => $user]);
        }
        return null;

    }
}
