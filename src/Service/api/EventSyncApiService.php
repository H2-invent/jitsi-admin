<?php

namespace App\Service\api;

use App\Repository\RoomStatusRepository;

class EventSyncApiService
{
    public function __construct(
        private RoomStatusRepository $roomStatusRepository
    )
    {
    }

    public function getCallerSessionFromUid(string $uid):array
    {
        $roomStatus = $this->roomStatusRepository->findRoomStatusByUid($uid);
        if ($roomStatus){
            return ['status'=>'ROOM_STARTED'];
        }else{
            return ['status'=>'ROOOM_CLOSED'];
        }
    }
}