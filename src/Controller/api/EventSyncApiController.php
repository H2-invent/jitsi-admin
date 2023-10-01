<?php

namespace App\Controller\api;

use App\Service\api\EventSyncApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/event/sync', name: 'app_event_sync_api')]
class EventSyncApiController extends AbstractController
{
    private $token;

    public function __construct(
        private ParameterBagInterface $parameterBag,
        private EventSyncApiService $eventSyncApiService,
    )
    {
        $this->token = 'Bearer ' . $this->parameterBag->get('JITSI_EVENTS_TOKEN');
    }

    #[Route('/', name: 'index')]
    public function index(Request $request): Response
    {
        $room_uid = $request->get('room_uid');
        return new JsonResponse($this->eventSyncApiService->getCallerSessionFromUid($room_uid));
    }
}
