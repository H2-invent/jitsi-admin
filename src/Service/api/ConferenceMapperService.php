<?php

namespace App\Service\api;

use App\Entity\CallerRoom;
use App\Repository\UserRepository;
use App\Service\LicenseService;
use App\Service\RoomService;
use App\Service\webhook\RoomStatusFrontendService;

class ConferenceMapperService
{
    public function __construct(
        private RoomStatusFrontendService $roomStatusFrontendService,
        private LicenseService            $licenseService,
        private RoomService               $roomService,
        private UserRepository            $userRepository
    )
    {
    }

    public function checkConference(?CallerRoom $callerRoom, $apiKey, $callerId)
    {

        if (!$callerRoom) {
            return ['error' => true, 'reason' => 'ROOM_NOT_FOUND'];
        }
        $room = $callerRoom->getRoom();
        $started = $this->roomStatusFrontendService->isRoomCreated($room);

        // skip beyond "Bearer "
        $apiKey = substr($apiKey, 7);
        $server = $room->getServer();
        if (!$server || !$this->licenseService->verify($server)) {
            return ['error' => true, 'text' => 'NO_SERVER_FOUND'];
        }
        if ($apiKey !== $server->getApiKey()) {
            return ['error' => true, 'text' => 'AUTHORIZATION_FAILED'];
        }
        if (!$started) {
            return [
                'state' => 'WAITING',
                'reason' => 'NOT_STARTED'
            ];
        }

        return [
            'state' => 'STARTED',
            'jwt' => $this->roomService->generateJwt($room, null, $callerId),
            'room_name' => $room->getUid() . '@' . $room->getServer()->getJigasiProsodyDomain()
        ];
    }

    public function findNameFromCallerId($callerId)
    {

    }
}
