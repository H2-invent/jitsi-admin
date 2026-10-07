<?php

namespace App\Controller\Api;

use App\Service\Api\EventSyncApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[\Symfony\Component\Routing\Attribute\Route('/api/v1/event/sync', name: 'app_event_sync_api')]
class EventSyncApiController extends AbstractController
{
    public function __construct(
        private readonly EventSyncApiService $eventSyncApiService,
    ) {
    }

    #[\Symfony\Component\Routing\Attribute\Route('/', name: 'index')]
    public function index(Request $request): Response
    {
        $room_uid = $request->get('room_uid');
        return new JsonResponse($this->eventSyncApiService->getCallerSessionFromUid($room_uid));
    }
}
