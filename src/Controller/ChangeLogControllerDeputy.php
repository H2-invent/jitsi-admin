<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Helper\JitsiAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ChangeLogControllerDeputy extends JitsiAdminController
{
    #[Route('room/change/log', name: 'app_change_log')]
    public function index(Request $request): Response
    {
        $room = $this->doctrine->getRepository(Rooms::class)->find($request->get('room_id'));
        if ($room->getModerator() !== $this->getUser()) {
            throw new NotFoundHttpException('Not found');
        }

        return $this->render(
            'change_log/index.html.twig',
            [
                'room' => $room,
            ]
        );
    }
}
