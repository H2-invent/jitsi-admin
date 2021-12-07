<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Service\RoomService;
use App\Service\StartMeetingService;
use App\Service\ThemeService;
use App\Service\TimeZoneService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class StartController extends AbstractController
{

    private $startService;
    public function __construct( StartMeetingService $startMeetingService)
    {
        $this->startService = $startMeetingService;
    }

    /**
     * @Route("/room/join/{t}/{room}", name="room_join")
     */
    public
    function joinRoom(RoomService $roomService, $room, $t)
    {
        $roomL = $this->getDoctrine()->getRepository(Rooms::class)->find($room);
        return $this->redirect($this->startService->startMeeting($roomL,$this->getUser(),$t));

    }
}
