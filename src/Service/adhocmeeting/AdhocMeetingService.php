<?php

namespace App\Service\adhocmeeting;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\Tag;
use App\Entity\User;
use App\Service\Callout\CalloutService;
use App\Service\Lobby\DirectSendService;
use App\Service\RoomGeneratorService;
use App\Service\ThemeService;
use App\Service\TimeZoneService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdhocMeetingService
{
    public function __construct(
        private EntityManagerInterface       $em,
        private RoomGeneratorService         $roomGeneratorService,
        private ParameterBagInterface        $parameterBag,
        private TranslatorInterface          $translator,
        private UserService                  $userService,
        private ThemeService                 $theme,
        private CalloutService               $calloutService,
        private AdhocMeetingWebsocketService $adhocMeetingWebsocketService,
    )
    {

    }

    public function createAdhocMeeting(User $creator, User $reciever, Server $server, Tag $tag = null): ?Rooms
    {
        $room = $this->roomGeneratorService->createRoom($creator, $server);
        if ($tag) {
            $room->setTag($tag);
        } else {
            $room->setTag(null);
        }
        $now = new \DateTime('now', TimeZoneService::getTimeZone($creator));
        $room->setStart($now);
        if ($this->theme->getApplicationProperties('allowTimeZoneSwitch') == 1) {
            $room->setTimeZone($creator->getTimeZone());
        }
        $room->setEnddate((clone $now)->modify('+ 1 hour'));
        $room->setDuration(60);
        $room->setName($this->translator->trans('Konferenz mit {n}', ['{n}' => $creator->getFormatedName($this->parameterBag->get('laf_showName'))]));
        $room->setSecondaryName($this->translator->trans('Konferenz mit {n}', ['{n}' => $reciever->getFormatedName($this->parameterBag->get('laf_showName'))]));
        $this->em->persist($room);
        $this->em->flush();
        $reciever->addRoom($room);
        $this->em->persist($reciever);
        $creator->addRoom($room);
        $this->em->persist($creator);
        $this->em->flush();
        $this->userService->addUser($reciever, $room);
        $this->userService->addUser($creator, $room);
        $this->calloutService->initCalloutSession($room, $reciever, $creator);
        return $room;
    }

}
