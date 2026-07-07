<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Helper\JitsiAdminController;
use App\Service\RoomService;
use App\Service\StartMeetingService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class StartController extends JitsiAdminController
{
    #[Route(path: '/room/join/{t}/{room}', name: 'room_join')]
    public function joinRoom(RoomService $roomService, $room, $t, StartMeetingService $startMeetingService)
    {
        $roomL = $this->doctrine->getRepository(Rooms::class)->find($room);
        return $startMeetingService->startMeeting($roomL, $this->getUser(), $t, $this->getUser()->getFormatedName($this->parameterBag->get('laf_showNameInConference')));
    }
    #[Route(path: '/room/checkCors', name: 'room_check_cors')]
    public function checkCorsRoom(Request $request, RoomService $roomService)
    {
        $weiterleitung = 'https://' . $request->get('url') . '/testRoom';
        return $this->render('start/corsTest.html.twig', ['serverUrl' => $request->get('url'), 'cors' => $request->get('cors'), 'weiterleitung' => $weiterleitung]);
    }
}
