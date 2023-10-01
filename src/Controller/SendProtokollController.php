<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Service\Summary\SendSummaryViaEmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SendProtokollController extends AbstractController
{
    public function __construct(
        private SendSummaryViaEmailService $sendSummaryViaEmailService
    )
    {
    }

    #[Route('room/send/summary/{id}', name: 'app_send_summary')]
    public function index(Rooms $room): Response
    {
        if($room->getModerator() === $this->getUser()){
            $this->sendSummaryViaEmailService->sendSummaryForRoom(rooms: $room);
        }
        return $this->redirectToRoute('dashboard');
    }
}
