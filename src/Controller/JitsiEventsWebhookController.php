<?php

namespace App\Controller;

use App\Service\webhook\RoomWebhookService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class JitsiEventsWebhookController extends AbstractController
{
    private $paramterBag;
    private $token;
    private $webhookService;
    /**
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(ParameterBagInterface $parameterBag, RoomWebhookService $roomCreatedWebhookService)
    {
        $this->paramterBag = $parameterBag;
        $this->token = 'Bearer '.$parameterBag->get('JITSI_EVENTS_TOKEN');
        $this->webhookService = $roomCreatedWebhookService;
    }

    /**
     * @Route("/jitsi/events/room/created", name="jitsi_events_webhook_create", methods={"POST"})
     */
    public function create(Request $request, LoggerInterface $logger): Response
    {
        $check = $this->checkHEader($request);
        if ($check) {
            return $check;
        }
        $data = json_decode($request->getContent(),true);
        return new JsonResponse(array('succcess' => $this->webhookService->startWebhook($data)));
    }
    /**
     * @Route("/jitsi/events/room/destroyed", name="jitsi_events_webhook_destroy", methods={"POST"})
     */
    public function destroy(Request $request, LoggerInterface $logger): Response
    {
        $check = $this->checkHEader($request);
        if ($check) {
            return $check;
        }
        $data = json_decode($request->getContent(),true);
        return new JsonResponse(array('succcess' => $this->webhookService->startWebhook($data)));
    }
    /**
     * @Route("/jitsi/events/occupant/joined", name="jitsi_events_webhook_joined", methods={"POST"})
     */
    public function joined(Request $request, LoggerInterface $logger): Response
    {
        $check = $this->checkHEader($request);
        if ($check) {
            return $check;
        }
        $data = json_decode($request->getContent(),true);
        return new JsonResponse(array('succcess' => $this->webhookService->startWebhook($data)));
    }
    /**
     * @Route("/jitsi/events/occupant/left", name="jitsi_events_webhook_left", methods={"POST"})
     */
    public function left(Request $request, LoggerInterface $logger): Response
    {
        $check = $this->checkHEader($request);
        if ($check) {
            return $check;
        }
        $data = json_decode($request->getContent(),true);
        return new JsonResponse(array('succcess' => $this->webhookService->startWebhook($data)));
    }


    private function checkHEader(Request $request): ?Response
    {
        $authHeader = $request->headers->get('Authorization');
        if ($authHeader !== $this->token ) {
            $array = array('authorized' => $authHeader.$this->token);
            $response = new JsonResponse($array, 401);
            return $response;
        }

        return null;

    }
}
