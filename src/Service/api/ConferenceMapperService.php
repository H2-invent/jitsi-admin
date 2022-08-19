<?php

namespace App\Service\api;

use App\Entity\CallerId;
use App\Entity\CallerRoom;
use App\Service\LicenseService;
use App\Service\RoomService;
use App\Service\webhook\RoomStatusFrontendService;
use Symfony\Component\HttpFoundation\JsonResponse;

class ConferenceMapperService
{
    public function __construct(private RoomStatusFrontendService $roomStatusFrontendService,
                                private LicenseService            $licenseService,
                                private RoomService               $roomService)
    {
    }

    public function checkConference(?CallerRoom $callerRoom, $apiKey, $callerId)
    {

        if (!$callerRoom) {
            return array('error' => true, 'reason' => 'ROOM_NOT_FOUND');
        }
        $room = $callerRoom->getRoom();
        $started = $this->roomStatusFrontendService->isRoomCreated($room);

        // skip beyond "Bearer "
        $apiKey = substr($apiKey, 7);
        $server = $room->getServer();
        if (!$server || !$this->licenseService->verify($server)) {
            return array('error' => true, 'text' => 'NO_SERVER_FOUND');
        }
        if ($apiKey !== $server->getApiKey()) {
            return array('error' => true, 'text' => 'AUTHORIZATION_FAILED');
        }
        if (!$started) {
            return array(
                'state' => 'WAITING',
                'reason' => 'NOT_STARTED'
            );
        }
        return array(
            'state' => 'STARTED',
            'jwt' => $this->roomService->generateJwt($room, null, $callerId),
            'room_name'=>$room->getUid()
        );
    }
}