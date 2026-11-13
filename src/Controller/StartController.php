<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Helper\JitsiAdminController;
use App\Service\StartMeetingService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StartController extends JitsiAdminController
{
    #[\Symfony\Component\Routing\Attribute\Route(path: '/room/join/{type}/{room}', name: 'room_join')]
    public function joinRoom(
        string $type,
        ?Rooms $room,
        StartMeetingService $startMeetingService
    ): NotFoundHttpException|RedirectResponse|Response {
        if ($room !== null && $room->getServer()->shouldProvisionNewServer()) {
            return $this->redirectToRoute('app_provisioner_create', ['uidReal' => $room->getUidReal()]);
        }

        /** @var string $showNameInConference */
        $showNameInConference = $this->parameterBag->get('laf_showNameInConference');

        return $startMeetingService->startMeeting($room, $this->getUser(), $type, $this->getUser()->getFormatedName($showNameInConference));
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: '/room/checkCors', name: 'room_check_cors')]
    public function checkCorsRoom(Request $request): Response
    {
        $redirectUrl = 'https://' . $request->get('url') . '/testRoom';

        return $this->render(
            'start/corsTest.html.twig',
            ['serverUrl' => $request->get('url'), 'cors' => $request->get('cors'), 'redirectUrl' => $redirectUrl]
        );
    }
}
