<?php

namespace App\Service;

use App\Entity\Rooms;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class StartMeetingService
{
    private $roomService;
    private $em;
    private $urlGen;
    private $parameterBag;
    private $translator;
    public function __construct(RoomService $roomService, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator, ParameterBagInterface $parameterBag, TranslatorInterface $translator)
    {
        $this->roomService = $roomService;
        $this->em = $entityManager;
        $this->urlGen = $urlGenerator;
        $this->parameterBag = $parameterBag;
        $this->translator = $translator;
    }

    public function startMeeting(?Rooms $room, User $user, $t)
    {
        if ($room && in_array($user, $room->getUser()->toarray())) {
            $url = $this->roomService->join($room, $user, $t, $user->getFormatedName($this->parameterBag->get('laf_showNameInConference')));
            if ($user === $room->getModerator() && $room->getTotalOpenRooms() && $room->getPersistantRoom()) {
                $room->setStart(new \DateTime());
                if ($room->getTotalOpenRoomsOpenTime()) {
                    $room->setEnddate((new \DateTime())->modify('+ ' . $room->getTotalOpenRoomsOpenTime() . ' min'));
                }

                $this->em->persist($room);
                $this->em->flush();
            }
            $now = new \DateTime();
            if ($room->getTimeZone()) {
                $now = new \DateTime('now', TimeZoneService::getTimeZone($user));
            }

            if (($room->getStart() === null || $room->getStartwithTimeZone($user)->modify('-30min') < $now && $room->getEndwithTimeZone($user) > $now) || $user === $room->getModerator()) {
                if ($room->getLobby()) {
                    $res = $this->urlGen->generate('dashboard');
                    if ($user === $room->getModerator() || $user->getPermissionForRoom($room)->getLobbyModerator()) {
                        $res = $this->urlGen->generate('lobby_moderator', array('uid' => $room->getUidReal(), 'type' => $t));
                    } else {
                        $res = $this->urlGen->generate('lobby_participants_wait', array('type' => $t, 'roomUid' => $room->getUidReal(), 'userUid' => $user->getUid()));
                    }
                    return $res;
                }
                return $url;
            }
            return $this->urlGen->generate('dashboard', ['color' => 'danger', 'snack' => $this->translator->trans('Der Beitritt ist nur von {from} bis {to} möglich',
                    array(
                        '{from}' => $room->getStartwithTimeZone($user)->format('d.m.Y H:i'),
                        '{to}' => $room->getEndwithTimeZone($user)->format('d.m.Y H:i')
                    ))
                ]
            );
        }
        return $this->urlGen->generate('dashboard', [
                'color' => 'danger',
                'snack' => $this->translator->trans('Konferenz nicht gefunden. Zugangsdaten erneut eingeben')
            ]
        );
    }
}
