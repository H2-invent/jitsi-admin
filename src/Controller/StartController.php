<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Helper\JitsiAdminController;
use App\Service\StartMeetingService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class StartController extends JitsiAdminController
{
    #[Route(path: '/room/join/{type}/{room}', name: 'room_join')]
    public function joinRoom(string $type, ?Rooms $room, StartMeetingService $startMeetingService)
    {
        if ($room !== null && $room->getServer()->shouldProvisionNewServer()) {
            return $this->redirectToRoute('app_provisioner_create', ['uidReal' => $room->getUidReal()]);
        }

        return $startMeetingService->startMeeting($room,
            $this->getUser(),
            $type,
            $this->getUser()->getFormatedName($this->parameterBag->get('laf_showNameInConference'))
        );
    }

    #[Route(path: '/room/checkCors', name: 'room_check_cors')]
    public function checkCorsRoom(Request $request)
    {
        $redirectUrl = 'https://' . $request->get('url') . '/testRoom';

        return $this->render('start/corsTest.html.twig',
            ['serverUrl' => $request->get('url'), 'cors' => $request->get('cors'), 'redirectUrl' => $redirectUrl]
        );
    }
}
