<?php

namespace App\Controller;

use App\Helper\JitsiAdminController;
use App\Service\api\CheckAuthorizationService;
use App\Service\webhook\RoomWebhookService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class JitsiEventsWebhookController extends JitsiAdminController
{
    private $token;
    private $webhookService;

    /**
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(
        ManagerRegistry       $managerRegistry,
        TranslatorInterface   $translator,
        LoggerInterface       $logger,
        ParameterBagInterface $parameterBag,
        RoomWebhookService    $roomCreatedWebhookService
    )
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);

        $this->token = 'Bearer ' . $parameterBag->get('JITSI_EVENTS_TOKEN');
        $this->webhookService = $roomCreatedWebhookService;
    }

    #[Route(path: '/jitsi/events/room/created', name: 'jitsi_events_webhook_create', methods: ['POST'])]
    public function create(Request $request, LoggerInterface $logger): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }
        $data = json_decode($request->getContent(), true);
        $res = $this->webhookService->startWebhook($data);
        $arr = ['success' => true];
        if ($res !== null) {
            $arr = ['succes' => false, 'error' => $res];
        }
        return new JsonResponse($arr);
    }

    #[Route(path: '/jitsi/events/room/destroyed', name: 'jitsi_events_webhook_destroy', methods: ['POST'])]
    public function destroy(Request $request, LoggerInterface $logger): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }
        $data = json_decode($request->getContent(), true);
        $res = $this->webhookService->startWebhook($data);
        $arr = ['success' => true];
        if ($res !== null) {
            $arr = ['succes' => false, 'error' => $res];
        }
        return new JsonResponse($arr);
    }

    #[Route(path: '/jitsi/events/occupant/joined', name: 'jitsi_events_webhook_joined', methods: ['POST'])]
    public function joined(Request $request, LoggerInterface $logger): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }
        $data = json_decode($request->getContent(), true);
        sleep(2);
        $res = $this->webhookService->startWebhook($data);
        $arr = ['success' => true];
        if ($res !== null) {
            $arr = ['succes' => false, 'error' => $res];
        }
        return new JsonResponse($arr);
    }

    #[Route(path: '/jitsi/events/occupant/left', name: 'jitsi_events_webhook_left', methods: ['POST'])]
    public function left(Request $request, LoggerInterface $logger): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }
        $data = json_decode($request->getContent(), true);
        $res = $this->webhookService->startWebhook($data);
        $arr = ['success' => true];
        if ($res !== null) {
            $arr = ['succes' => false, 'error' => $res];
        }
        return new JsonResponse($arr);
    }
}
