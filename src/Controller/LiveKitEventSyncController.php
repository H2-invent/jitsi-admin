<?php

namespace App\Controller;

use Agence104\LiveKit\WebhookReceiver;
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
    private $eventId;
    private WebhookReceiver $webhookReceiver;

    public function __construct(
        private RoomWebhookService    $webhookService,
        private ParameterBagInterface $parameterBag,
        private LoggerInterface       $logger
    )
    {
        $this->token = $this->parameterBag->get('LIVEKIT_EVENT_SECRET');
        $this->eventId = $this->parameterBag->get('LIVEKIT_EVENT_ID');
        $this->webhookReceiver = new WebhookReceiver($this->eventId, $this->token);
    }

    #[Route('/livekit/event', name: 'app_live_kit_event_sync')]
    public function index(Request $request): Response
    {
        $this->logger->debug('livekit', ['message' => 'receive new livekit event']);
        $event = null;
        $content = $request->getContent();
        $this->logger->debug('livekit content from request', ['content' => $content]);
        try {
            $event = $this->webhookReceiver->receive($content, null, true);
            $this->logger->debug('livekit event as json',['json'=>$event->serializeToString()]);
        } catch (\Exception $exception) {
            $this->logger->debug('livekit error', ['message' => 'Invalid event token found']);

            $array = ['authorized' => false];
            $response = new JsonResponse($array, 401);
            return $response;
        }


        $this->logger->debug('livekit event token valid');
        $event->serializeToString();
        $eventType = $event->getEvent();
        $res = ['error' => false];
        $this->logger->debug('Event found', ['event' => $eventType]);
        switch ($eventType) {
            case 'room_finished':
                $res = $this->webhookService->roomDestroyed(false,
                    null,
                    $event->getRoom()->getSid(),
                    $event->getCreatedAt()
                );
                break;
            case 'room_started':
                $res = $this->webhookService->roomCreated(
                    $event->getRoom()->getName(),
                    false,
                    null,
                    $event->getRoom()->getSid(),
                    $event->getRoom()->getCreationTime()
                );
                break;
            case 'participant_left':
                $res = $this->webhookService->roomParticipantLeft(
                    false,
                    null,
                    $event->getParticipant()->getSid(),
                    $event->getCreatedAt(),
                    null
                );
                break;
            case 'participant_joined':
                $res = $this->webhookService->roomParticipantJoin(
                    false,
                    null,
                    $event->getRoom()->getSid(),
                    $event->getParticipant()->getSid(),
                    $event->getParticipant()->getJoinedAt(),
                    $event->getParticipant()->getName()
                );
                break;
            default:
                $this->logger->error('unregistered Event found', ['event' => $eventType]);
                break;
        }
        if (!$res) {
            $res = ['error' => false];
        } else {
            $res = [
                'error' => $res
            ];
        }
        return new JsonResponse($res);

    }

}

;