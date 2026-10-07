<?php

namespace App\Controller;

use App\Helper\JitsiAdminController;
use App\Service\PushService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends JitsiAdminController
{
    #[\Symfony\Component\Routing\Attribute\Route(path: '/room/notification', name: 'notification')]
    public function index(PushService $pushService): Response
    {
        return new JsonResponse($pushService->getNotification($this->getUser()));
    }
}
