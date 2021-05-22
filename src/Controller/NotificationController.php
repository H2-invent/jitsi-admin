<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Service\PushService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationController extends AbstractController
{
    /**
     * @Route("/room/notification", name="notification")
     */
    public function index(PushService $pushService): Response
    {
        return new JsonResponse($pushService->getNotification($this->getUser()));
    }
}
