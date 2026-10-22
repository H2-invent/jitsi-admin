<?php

namespace App\Controller\api;

use App\Helper\JitsiAdminController;
use App\Service\api\CheckAuthorizationService;
use App\Service\Callout\CallOutSessionAPIDialService;
use App\Service\Callout\CallOutSessionAPIHoldService;
use App\Service\Callout\CallOutSessionAPIRemoveService;
use App\Service\Callout\CalloutSessionAPIService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/v1/call/out', name: 'callout_api_')]
class CalloutAPIController extends JitsiAdminController
{
    private string $token;

    public function __construct(
        ManagerRegistry                      $managerRegistry,
        TranslatorInterface                  $translator,
        LoggerInterface                      $logger,
        ParameterBagInterface                $parameterBag,
        private CalloutSessionAPIService     $calloutSessionAPIService,
        private CallOutSessionAPIDialService $callOutSessionAPIDialService,
    )
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
        $this->token = 'Bearer ' . $parameterBag->get('SIP_CALLER_SECRET');
    }

    #[Route('/', name: 'pool')]
    public function index(Request $request): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }
        $calloutSessions = $this->calloutSessionAPIService->getCalloutPool();
        return new JsonResponse($calloutSessions);
    }

    #[Route('/dial/', name: 'dial_pool', methods: 'GET')]
    public function dialPool(Request $request): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }

        $res = $this->calloutSessionAPIService->getDialPool();
        return new JsonResponse($res);
    }

    #[Route('/dial/{calloutSessionId}', name: 'dial')]
    public function dial(string $calloutSessionId, Request $request): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }

        $res = $this->callOutSessionAPIDialService->dialSession($calloutSessionId);
        return new JsonResponse($res);
    }

    #[Route('/refuse/{calloutSessionId}', name: 'refuse')]
    public function refuse(string $calloutSessionId, Request $request, CallOutSessionAPIRemoveService $callOutSessionAPIRemoveService): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }
        $res = $callOutSessionAPIRemoveService->refuse($calloutSessionId);
        return new JsonResponse($res);
    }

    #[Route('/error/{calloutSessionId}', name: 'error')]
    public function error(string $calloutSessionId, Request $request, CallOutSessionAPIRemoveService $callOutSessionAPIRemoveService): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }
        $res = $callOutSessionAPIRemoveService->error($calloutSessionId);
        return new JsonResponse($res);
    }

    #[Route('/unreachable/{calloutSessionId}', name: 'unreachable')]
    public function unreachable(string $calloutSessionId, Request $request, CallOutSessionAPIRemoveService $callOutSessionAPIRemoveService): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }
        $res = $callOutSessionAPIRemoveService->unreachable($calloutSessionId);
        return new JsonResponse($res);
    }


    #[Route('/timeout/{calloutSessionId}', name: 'timeout')]
    public function timeout(string $calloutSessionId, Request $request, CallOutSessionAPIHoldService $callOutSessionAPIHoldService): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }
        $res = $callOutSessionAPIHoldService->timeout($calloutSessionId);
        return new JsonResponse($res);
    }


    #[Route('/later/{calloutSessionId}', name: 'later')]
    public function later(string $calloutSessionId, Request $request, CallOutSessionAPIHoldService $callOutSessionAPIHoldService): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }
        $res = $callOutSessionAPIHoldService->later($calloutSessionId);
        return new JsonResponse($res);
    }

    #[Route('/occupied/{calloutSessionId}', name: 'occupied')]
    public function occupied(string $calloutSessionId, Request $request, CallOutSessionAPIHoldService $callOutSessionAPIHoldService): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }
        $res = $callOutSessionAPIHoldService->occupied($calloutSessionId);
        return new JsonResponse($res);
    }

    #[Route('/ringing/{calloutSessionId}', name: 'ringing')]
    public function ringing(string $calloutSessionId, Request $request, CallOutSessionAPIHoldService $callOutSessionAPIHoldService): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }
        $res = $this->callOutSessionAPIDialService->ringing($calloutSessionId);
        return new JsonResponse($res);
    }

    #[Route('/on_hold/', name: 'on_hold_pool', methods: 'GET')]
    public function onHoldPool(Request $request): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }

        $res = $this->calloutSessionAPIService->getOnHoldPool();
        return new JsonResponse($res);
    }

    #[Route('/back/{calloutSessionId}', name: 'back', methods: 'GET')]
    public function back(string $calloutSessionId, Request $request, CallOutSessionAPIHoldService $callOutSessionAPIHoldService): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }
        $res = $this->callOutSessionAPIDialService->backSession($calloutSessionId);
        return new JsonResponse($res);
    }
}
