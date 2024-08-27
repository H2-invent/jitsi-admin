<?php

namespace App\Controller;

use App\Service\api\CheckAuthorizationService;
use App\Service\webhook\RoomWebhookService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LiveKitEventSyncController extends AbstractController
{
    private $token;
    public function __construct(
        private RoomWebhookService $webhookService,
        private ParameterBagInterface $parameterBag,
        private LoggerInterface $logger
    )
    {
        $this->token = 'Bearer '. $this->parameterBag->get('LIVEKIT_EVENT_TOKEN');
    }

    #[Route('/livekit/event', name: 'app_live_kit_event_sync')]
    public function index(Request $request): Response
    {
        $check = CheckAuthorizationService::checkHEader($request,$this->token);
        if ($check){
            return $check;
        }
        $data = json_decode($request->get('data'),true);
        $eventType = $data['event'];
        switch ($eventType){
            case 'room_finished':

                break;
            case 'room_started':
                break;
            case 'participant_left':
                break;
            case 'participant_joined':
                break;
            default:
                $this->logger->error('unregistered Event found',['event'=>$eventType]);
                break;
        }
        return $this->render('live_kit_event_sync/index.html.twig', [
            'controller_name' => 'LiveKitEventSyncController',
        ]);
    }
}
;