<?php

namespace App\Controller;

use App\Service\api\CheckAuthorizationService;
use App\Service\webhook\RoomWebhookService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LiveKitEventSyncController extends AbstractController
{
    private $token;

    public function __construct(
        private RoomWebhookService    $webhookService,
        private ParameterBagInterface $parameterBag,
        private LoggerInterface       $logger
    )
    {
        $this->token = 'Bearer ' . $this->parameterBag->get('LIVEKIT_EVENT_TOKEN');
    }

    #[Route('/livekit/event', name: 'app_live_kit_event_sync')]
    public function index(Request $request): Response
    {
        $this->logger->debug('recieve new event');
        $check = CheckAuthorizationService::checkHEader($request, $this->token);

        if ($check) {
            $this->logger->debug('Invalid event token found');
            return $check;
        }
        $this->logger->debug('Valid event token found');
        $data = json_decode($request->getContent(), true);
        $eventType = $data['event'];
        $res = ['error' => false];
        $this->logger->debug('Event found',['event'=>$eventType]);
        switch ($eventType) {
            case 'room_finished':
                $res = $this->webhookService->roomDestroyed(false,
                    null,
                    $data['room']['sid'],
                    $data['createdAt']
                );
                break;
            case 'room_started':
                $res = $this->webhookService->roomCreated(
                    $data['room']['name'],
                    false,
                    null,
                    $data['room']['sid'],
                    $data['room']['creationTime']
                );
                break;
            case 'participant_left':
                $res = $this->webhookService->roomParticipantLeft(
                    false,
                    null,
                    $data['participant']['sid'],
                    $data['createdAt'],
                    null
                );
                break;
            case 'participant_joined':
                $res =  $this->webhookService->roomParticipantJoin(false, null,
                    $data['room']['sid'],
                    $data['participant']['sid'],
                    $data['participant']['joinedAt'],
                    $data['participant']['name']
                );
                break;
            default:
                $this->logger->error('unregistered Event found', ['event' => $eventType]);
                break;
        }
        if (!$res){
            $res = ['error' => false];
        }else{
            $res=[
                'error'=>$res
            ];
        }
        return new JsonResponse($res);

    }

}

;