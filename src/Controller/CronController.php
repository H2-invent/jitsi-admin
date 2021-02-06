<?php

namespace App\Controller;


use App\Entity\Rooms;
use App\Service\ReminderService;
use App\Service\UserService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CronController extends AbstractController
{
    /**
     * @Route("/cron/remember", name="cron_remember")
     */
    public function updateCronAkademie(Request $request, LoggerInterface $logger, UserService $userService, ReminderService $reminderService)
    {
        if ($request->get('token') !== $this->getParameter('cronToken')) {
            $message = ['error' => true, 'hinweis' => 'Token fehlerhaft', 'token' => $request->get('token'), 'ip' => $request->getClientIp()];
            $logger->error($message['hinweis'], $message);
            return new JsonResponse($message);
        }
        return new JsonResponse($reminderService->sendReminder());
    }
}
