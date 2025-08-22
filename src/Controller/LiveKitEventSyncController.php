<?php

namespace App\Controller;

use Agence104\LiveKit\WebhookReceiver;
use App\Entity\RoomStatus;
use App\Repository\RoomsRepository;
use App\Repository\RoomStatusRepository;
use App\Service\api\CheckAuthorizationService;
use App\Service\livekit\EgressService;
use App\Service\webhook\RoomWebhookService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LiveKitEventSyncController extends AbstractController
{

    private WebhookReceiver $webhookReceiver;

    public function __construct(
        private RoomWebhookService   $webhookService,
        private LoggerInterface      $logger,
        private RoomsRepository      $roomsRepository,
        private EgressService        $egressService,
        private RoomStatusRepository $roomStatusRepository,
        private HttpClientInterface $httpClient,
        private ParameterBagInterface $parameterBag,
    )
    {
        $this->webhookReceiver = new WebhookReceiver('test', 'test');
    }

    #[Route('/livekit/event', name: 'app_live_kit_event_sync')]
    public function index(Request $request): Response
    {
        $this->logger->debug('livekit', ['message' => 'receive new livekit event']);
        $event = null;
        $content = $request->getContent();
        $this->logger->debug('livekit content from request', ['content' => $content]);
        try {
            $this->logger->debug('livekit before parsing content');
            $event = $this->webhookReceiver->receive($content, null, true);
            $this->logger->debug('livekit event as json', ['json' => $event->serializeToJsonString()]);
        } catch (\Exception $exception) {
            $this->logger->error('livekit error', ['message' => $exception->getMessage()]);
            $this->logger->debug('livekit error', ['message' => 'Invalid event token found']);

            $array = ['authorized' => false];
            $response = new JsonResponse($array, 401);
            return $response;
        }


        $this->logger->debug('livekit event token valid');
        $eventType = $event->getEvent();
        $rawRoomName = $event->getRoom()->getName();
        $this->logger->debug('Roomname in Event', [$rawRoomName]);

        $roomNameParts = explode('@', $rawRoomName);
        $roomName = $roomNameParts[0];
        $roomSid = $event->getRoom()->getSid();
        $this->logger->debug('Roomname in Event',[$roomName]);
        $this->logger->debug('SID in Event',[$roomSid]);
        $room = $this->roomsRepository->findOneBy(['uid' => $roomName]);
        if ($room){
            try {
                $targetUrl  = $room->getServer()->getLivekitMiddlewareUrl()?:$this->parameterBag->get('LIVEKIT_BASE_URL');
                $targetUrl.='/webhook/recieve';
                $this->logger->debug('livekit relay', ['target' => $targetUrl]);

                $relayResponse = $this->httpClient->request('POST', $targetUrl, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => $content,
                ]);

                $statusCode = $relayResponse->getStatusCode();
                $relayBody = $relayResponse->getContent(false); // false to prevent exceptions on non-2xx

                $this->logger->debug('livekit relay response', ['status' => $statusCode, 'body' => $relayBody]);
            } catch (\Exception $e) {
                $this->logger->error('livekit relay error', ['message' => $e->getMessage()]);
            }
        }

        if (preg_match('/@.*_lobby_/', $rawRoomName)) {
            $this->logger->info('Lobby-Raum erkannt, Request wird beendet.', ['room' => $rawRoomName]);
            return new JsonResponse(['status' => 'ignored_lobby_room']);
        }

        $res = ['error' => false];
        $this->logger->debug('livekit Event found', ['event' => $eventType]);
        switch ($eventType) {
            case 'room_finished':
                $res = $this->webhookService->roomDestroyed(false,
                    null,
                    $roomSid,
                    $event->getCreatedAt()
                );
                $roomStatus = $this->roomStatusRepository->findCreatedRoomsbyJitsiId($roomSid);
                if ($roomStatus){
                    $this->egressService->stopAllEgress($roomStatus->getRoom());
                }
                break;
            case 'room_started':
                $res = $this->webhookService->roomCreated(
                    $roomName,
                    false,
                    null,
                    $roomSid,
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
                    $roomSid,
                    $event->getParticipant()->getSid(),
                    $event->getParticipant()->getJoinedAt(),
                    $event->getParticipant()->getName()
                );
                break;
            default:
                $this->logger->debug('unregistered Event found', ['event' => $eventType]);
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