<?php

namespace App\Service;

use App\Entity\CallerRoom;
use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Service\caller\CallerPrepareService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RoomGeneratorService
{
    private $parameterBag;
    private $callerPrepareService;
    public function __construct(ParameterBagInterface $parameterBag, CallerPrepareService $callerPrepareService)
    {
        $this->parameterBag = $parameterBag;
        $this->callerPrepareService = $callerPrepareService;
    }
    public function createRoom(User $user, ?Server $server = null):Rooms{
        $room = new Rooms();
        $room->setServer($server);
        $room->addUser($user);
        $room->setDuration(60);
        $room->setUid(rand(01, 99) . time());
        $room->setModerator($user);
        $room->setSequence(0);
        $room->setUidReal(md5(uniqid('h2-invent', true)));
        $room->setUidModerator(md5(uniqid('h2-invent', true)));
        $room->setUidParticipant(md5(uniqid('h2-invent', true)));
        // here we set the default values
        $room->setPersistantRoom($this->parameterBag->get('input_settings_persistant_rooms_default'));
        $room->setOnlyRegisteredUsers($this->parameterBag->get('input_settings_only_registered_default'));
        $room->setPublic($this->parameterBag->get('input_settings_share_link_default'));
        if ($this->parameterBag->get('input_settings_max_participants_default') > 0) {
            $room->setMaxParticipants($this->parameterBag->get('input_settings_max_participants_default'));
        }
        $room->setWaitinglist($this->parameterBag->get('input_settings_waitinglist_default'));
        $room->setShowRoomOnJoinpage($this->parameterBag->get('input_settings_conference_join_page_default'));
        $room->setTotalOpenRooms($this->parameterBag->get('input_settings_deactivate_participantsList_default'));
        $room->setDissallowScreenshareGlobal($this->parameterBag->get('input_settings_dissallow_screenshare_default'));
        $room->setLobby($this->parameterBag->get('input_settings_allowLobby_default'));

        //end default values

        if ($user->getTimeZone() && $this->parameterBag->get('allowTimeZoneSwitch') == 1) {
            $room->setTimeZone($user->getTimeZone());
            if ($this->parameterBag->get('input_settings_allow_timezone_default') != 0) {
                $room->setTimeZone($this->parameterBag->get('input_settings_allow_timezone_default'));
            }
        }
        $roomCaller = new CallerRoom();
        $roomCaller->setCallerId($this->callerPrepareService->generateRoomId(999999));
        $roomCaller->setCreatedAt(new \DateTime());
        $room->setCallerRoom($roomCaller);
        return $room;
    }
}