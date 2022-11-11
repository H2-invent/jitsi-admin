<?php

namespace App\Service\adhocmeeting;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\Tag;
use App\Entity\User;
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
    private EntityManagerInterface $em;
    private RoomGeneratorService $roomGeneratorService;
    private ParameterBagInterface $parameterBag;
    private TranslatorInterface $translator;
    private DirectSendService $directSendService;
    private UserService $userService;
    private UrlGeneratorInterface $urlGen;
    private $theme;

    public function __construct(EntityManagerInterface $entityManager,
                                RoomGeneratorService   $roomGeneratorService,
                                ParameterBagInterface  $parameterBag,
                                TranslatorInterface    $translator,
                                DirectSendService      $directSendService,
                                UserService            $userService,
                                UrlGeneratorInterface  $urlGenerator,
                                ThemeService           $themeService
    )
    {
        $this->em = $entityManager;
        $this->roomGeneratorService = $roomGeneratorService;
        $this->parameterBag = $parameterBag;
        $this->translator = $translator;
        $this->directSendService = $directSendService;
        $this->userService = $userService;
        $this->urlGen = $urlGenerator;
        $this->theme = $themeService;
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
        $room->setName($this->translator->trans('Konferenz mit {n}', array('{n}' => $reciever->getFormatedName($this->parameterBag->get('laf_showName')))));
        $this->em->persist($room);
        $this->em->flush();
        $reciever->addRoom($room);
        $this->em->persist($reciever);
        $creator->addRoom($room);
        $this->em->persist($creator);
        $this->em->flush();
        $topic = 'personal/' . $reciever->getUid();
        $this->directSendService->sendCallAdhockmeeding(
            $this->translator->trans('addhock.notification.title'),
            $topic,
            $this->translator->trans('addhock.notification.message', array('{url}' => $this->urlGen->generate('room_join', array('room' => $room->getId(), 't' => 'b')), '{name}' => $creator->getFormatedName($this->parameterBag->get('laf_showName')))),
            $this->translator->trans('addhock.notification.pushMessage', array('{name}' => $creator->getFormatedName($this->parameterBag->get('laf_showName')))),
            60000,
            $room->getUid()
        );
        $this->userService->addUser($reciever, $room);
        $this->userService->addUser($creator, $room);
        return $room;
    }
}