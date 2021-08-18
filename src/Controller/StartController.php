<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Service\RoomService;
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
     * @ParamConverter("room", options={"mapping"={"room"="id"}})
     */
    public
    function joinRoom(RoomService $roomService, Rooms $room, $t)
    {

        if (in_array($this->getUser(), $room->getUser()->toarray())) {
            $url = $roomService->join($room, $this->getUser(), $t, $this->getUser()->getFirstName() . ' ' . $this->getUser()->getLastName());
            if ($this->getUser() == $room->getModerator() && $room->getTotalOpenRooms() && $room->getPersistantRoom()) {
                $room->setStart(new \DateTime());
                if ($room->getTotalOpenRoomsOpenTime()) {
                    $room->setEnddate((new \DateTime())->modify('+ ' . $room->getTotalOpenRoomsOpenTime() . ' min'));
                }
                $em = $this->getDoctrine()->getManager();
                $em->persist($room);
                $em->flush();
            }
            $now = new \DateTime('now',new \DateTimeZone('utc'));
            if (($room->getStart() === null || $room->getStartUtc()->modify('-30min') < $now && $room->getEndDateUtc() > $now) || $this->getUser() == $room->getModerator()) {
                return $this->redirect($url);
            }
            return $this->redirectToRoute('dashboard', ['color' => 'danger', 'snack' => $this->translator->trans('Der Beitritt ist nur von {from} bis {to} möglich',
                    array(
                        '{from}' => $room->getStartwithTimeZone($this->getUser())->modify('-30min')->format('d.m.Y H:i'),
                        '{to}' => $room->getEndwithTimeZone($this->getUser())->format('d.m.Y H:i')
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
