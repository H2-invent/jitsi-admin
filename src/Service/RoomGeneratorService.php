<?php

namespace App\Service;

use App\Entity\CallerRoom;
use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\Tag;
use App\Entity\User;
use App\Service\caller\CallerPrepareService;
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
        $room->setSequence(0);
        $room->setUidReal(md5(uniqid('h2-invent', true)));
        $room->setUidModerator(md5(uniqid('h2-invent', true)));
        $room->setUidParticipant(md5(uniqid('h2-invent', true)));
        // here we set the default values
        $room->setPersistantRoom($this->themeService->getApplicationProperties('input_settings_persistant_rooms_default'));
        $room->setOnlyRegisteredUsers($this->themeService->getApplicationProperties('input_settings_only_registered_default'));
        $room->setPublic($this->themeService->getApplicationProperties('input_settings_share_link_default'));
        if ($this->themeService->getApplicationProperties('input_settings_max_participants_default') > 0) {
            $room->setMaxParticipants($this->themeService->getApplicationProperties('input_settings_max_participants_default'));
        }
        $room->setWaitinglist($this->themeService->getApplicationProperties('input_settings_waitinglist_default'));
        $room->setShowRoomOnJoinpage($this->themeService->getApplicationProperties('input_settings_conference_join_page_default'));
        $room->setTotalOpenRooms($this->themeService->getApplicationProperties('input_settings_deactivate_participantsList_default'));
        $room->setDissallowScreenshareGlobal($this->themeService->getApplicationProperties('input_settings_dissallow_screenshare_default'));
        $room->setLobby($this->themeService->getApplicationProperties('input_settings_allowLobby_default'));

        //end default values

        if ($user->getTimeZone() && $this->themeService->getApplicationProperties('allowTimeZoneSwitch') == 1) {
            $room->setTimeZone($user->getTimeZone());
            if ($this->themeService->getApplicationProperties('input_settings_allow_timezone_default') != 0) {
                $room->setTimeZone($this->themeService->getApplicationProperties('input_settings_allow_timezone_default'));
            }
        }
        $room = $this->createCallerId($room);
        if ($this->parameterBag->get('input_settings_allow_tag') == 1) {
            $tag = $this->em->getRepository(Tag::class)->findOneBy(array('disabled' => false), array('priority' => 'ASC'));
            if ($tag) {
                $room->setTag($tag);
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
}