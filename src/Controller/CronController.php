<?php

namespace App\Controller;


use App\Service\NotificationService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CronController extends AbstractController
{
    /**
     * @Route("/cron/akademie_update", name="cron_akademie")
     */
    public function updateCronAkademie(NotificationService $notificationService, Request $request, LoggerInterface $logger)
    {
        $today = new \DateTime();

        if ($request->get('token') !== $this->getParameter('cronToken')) {
            $message = ['error' => true, 'hinweis' => 'Token fehlerhaft', 'token' => $request->get('token'), 'ip' => $request->getClientIp()];
            $logger->error($message['hinweis'], $message);
            return new JsonResponse($message);
        }

        return new JsonResponse($message);
    }
}
