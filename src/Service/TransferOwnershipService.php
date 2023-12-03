<?php

namespace App\Service;

use App\Entity\Rooms;
use App\Entity\User;
use App\Repository\RoomsUserRepository;
use Doctrine\ORM\EntityManagerInterface;

class TransferOwnershipService
{

    public function __construct(
        private EntityManagerInterface  $entityManager,
        private PermissionChangeService $permissionChangeService,
        private RoomsUserRepository     $roomsUserRepository,
    )
    {
    }

    public function transferOwnership(User $newOwner, Rooms $room): Rooms
    {
        $roomPermisison = $this->roomsUserRepository->findOneBy(['user'=>$room->getModerator(),'room'=>$room]);
        if (!$roomPermisison || !$roomPermisison->getModerator()){
            $this->permissionChangeService->toggleModerator($room->getModerator(), $room->getModerator(), $room);
        }
        if (!$roomPermisison ||!$roomPermisison->getLobbyModerator()){
            $this->permissionChangeService->toggleLobbyModerator($room->getModerator(), $room->getModerator(), $room);
        }

        if ($room->getModerator() === $room->getCreator()) {
            $room->setCreator($newOwner);
        }
        $room->setModerator($newOwner);
        $this->entityManager->persist($room);
        $this->entityManager->flush();
        return $room;
    }
}