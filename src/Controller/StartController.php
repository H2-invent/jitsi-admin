<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Service\RoomService;
use App\Service\ThemeService;
use App\Service\TimeZoneService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class StartController extends AbstractController
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @Route("/room/join/{t}/{room}", name="room_join")
     */
    public
    function joinRoom(RoomService $roomService, $room, $t)
    {
        $roomL = $this->getDoctrine()->getRepository(Rooms::class)->find($room);
        if ($roomL && in_array($this->getUser(), $roomL->getUser()->toarray())) {
            $url = $roomService->join($roomL, $this->getUser(), $t, $this->getUser()->getFirstName() . ' ' . $this->getUser()->getLastName());
            if ($this->getUser() == $roomL->getModerator() && $roomL->getTotalOpenRooms() && $roomL->getPersistantRoom()) {
                $roomL->setStart(new \DateTime());
                if ($roomL->getTotalOpenRoomsOpenTime()) {
                    $roomL->setEnddate((new \DateTime())->modify('+ ' . $roomL->getTotalOpenRoomsOpenTime() . ' min'));
                }
                $em = $this->getDoctrine()->getManager();
                $em->persist($roomL);
                $em->flush();
            }
            $now = new \DateTime();
            if ($roomL->getTimeZone()){
                $now = new \DateTime('now',TimeZoneService::getTimeZone($this->getUser()));
            }

            if (($roomL->getStart() === null || $roomL->getStartwithTimeZone($this->getUser())->modify('-30min') < $now && $roomL->getEndwithTimeZone($this->getUser()) > $now) || $this->getUser() == $roomL->getModerator()) {
                return $this->redirect($url);
            }
            return $this->redirectToRoute('dashboard', ['color' => 'danger', 'snack' => $this->translator->trans('Der Beitritt ist nur von {from} bis {to} möglich',
                    array(
                        '{from}' => $roomL->getStartwithTimeZone($this->getUser())->format('d.m.Y H:i'),
                        '{to}' => $roomL->getEndwithTimeZone($this->getUser())->format('d.m.Y H:i')
                    ))
                ]
            );
        }

        return $this->redirectToRoute('dashboard', [
                'color' => 'danger',
                'snack' => $this->translator->trans('Konferenz nicht gefunden. Zugangsdaten erneut eingeben')
            ]
        );
    }
}
