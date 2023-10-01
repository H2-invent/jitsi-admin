<?php

namespace App\Service;

use App\Entity\CallerRoom;
use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\Tag;
use App\Entity\User;
use App\Service\caller\CallerPrepareService;
use App\Util\InputSettings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class RoomGeneratorService
{
    private $parameterBag;
    private $callerPrepareService;
    private $em;
    private RequestStack $requestStack;
    private ThemeService $themeService;

    public function __construct(RequestStack $requestStack, ParameterBagInterface $parameterBag, CallerPrepareService $callerPrepareService, EntityManagerInterface $entityManager, ThemeService $themeService)
    {
        $this->parameterBag = $parameterBag;
        $this->callerPrepareService = $callerPrepareService;
        $this->em = $entityManager;
        $this->requestStack = $requestStack;
        $this->themeService = $themeService;
    }

    public function createRoom(User $user, ?Server $server = null): Rooms
    {
        $room = new Rooms();
        if ($this->requestStack && $this->requestStack->getCurrentRequest()) {
            $room->setHostUrl($this->requestStack->getCurrentRequest()->getSchemeAndHttpHost());
        }
        $room->setServer($server);
        $room->addUser($user);
        $room->setDuration(60);
        $room->setUid(rand(01, 99) . time());
        $room->setModerator($user);
        $room->setCreator($user);
        $room->setSequence(0);
        $room->setUidReal(md5(uniqid('h2-invent', true)));
        $room->setUidModerator(md5(uniqid('h2-invent', true)));
        $room->setUidParticipant(md5(uniqid('h2-invent', true)));
        // here we set the default values
        $room->setPersistantRoom($this->themeService->getApplicationProperties(InputSettings::PERSISTENT_ROOMS_DEFAULT));
        $room->setOnlyRegisteredUsers($this->themeService->getApplicationProperties(InputSettings::ONLY_REGISTERED_DEFAULT));
        $room->setPublic($this->themeService->getApplicationProperties(InputSettings::SHARE_LINK_DEFAULT));
        if ($this->themeService->getApplicationProperties(InputSettings::MAX_PARTICIPANTS_DEFAULT) > 0) {
            $room->setMaxParticipants($this->themeService->getApplicationProperties(InputSettings::MAX_PARTICIPANTS_DEFAULT));
        }
        $room->setWaitinglist($this->themeService->getApplicationProperties(InputSettings::WAITING_LIST_DEFAULT));
        $room->setShowRoomOnJoinpage($this->themeService->getApplicationProperties(InputSettings::CONFERENCE_JOIN_PAGE_DEFAULT));
        $room->setTotalOpenRooms($this->themeService->getApplicationProperties(InputSettings::DEACTIVATE_PARTICIPANTS_LIST_DEFAULT));
        $room->setDissallowScreenshareGlobal($this->themeService->getApplicationProperties(InputSettings::DISALLOW_SCREENSHARE_DEFAULT));
        $room->setLobby($this->themeService->getApplicationProperties(InputSettings::ALLOW_LOBBY_DEFAULT));
        $room->setMaxUser($this->themeService->getApplicationProperties(InputSettings::ALLOW_SET_MAX_USERS_DEFAULT) != '' ? InputSettings::ALLOW_SET_MAX_USERS_DEFAULT : null);
        //end default values

        if ($user->getTimeZone() && $this->themeService->getApplicationProperties('allowTimeZoneSwitch') == 1) {
            $room->setTimeZone($user->getTimeZone());
            if ($this->themeService->getApplicationProperties(InputSettings::ALLOW_TIMEZONE_DEFAULT) != 0) {
                $room->setTimeZone($this->themeService->getApplicationProperties(InputSettings::ALLOW_TIMEZONE_DEFAULT));
            }
        }
        $room = $this->createCallerId($room);

        if ($this->parameterBag->get(InputSettings::ALLOW_TAG) == 1) {
            if ($server) {
                if ($server->getTag()->count() > 0) {
                    $room->setTag($server->getTag()->first());
                }
            }
        }

        return $room;
    }

    public function createCallerId(Rooms $room)
    {
        $roomCaller = new CallerRoom();
        $roomCaller->setCallerId($this->callerPrepareService->generateRoomId(999999));
        $roomCaller->setCreatedAt(new \DateTime());
        $room->setCallerRoom($roomCaller);
        return $room;
    }

    public function addUserToRoom(User $user, Rooms $rooms, $cleanParticipantsBefore = false): Rooms
    {
        if ($cleanParticipantsBefore) {
            foreach ($rooms->getUser() as $data) {
                $rooms->removeUser($data);
            }
        }
        $rooms->addUser($user);
        $this->em->persist($rooms);
        $this->em->flush();
        return $rooms;
    }

}
