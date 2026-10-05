<?php

namespace App\Controller\Api;

use App\Helper\JitsiAdminController;
use App\Service\Api\CheckAuthorizationService;
use App\Service\Caller\CallerFindRoomService;
use App\Service\Caller\CallerLeftService;
use App\Service\Caller\CallerPinService;
use App\Service\Caller\CallerSessionService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class CallerController extends JitsiAdminController
{
    private readonly string $token;

    public function __construct(
        ManagerRegistry                       $managerRegistry,
        TranslatorInterface                   $translator,
        LoggerInterface                       $logger,
        ParameterBagInterface                 $parameterBag,
        private readonly CallerLeftService                     $callerLeftService,
        private readonly CallerSessionService                  $callerSessionService,
        private readonly CallerPinService                      $callerPinService,
        private readonly CallerFindRoomService                 $callerRoomService,
    )
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
        /** @var string $sipCallerSecret */
        $sipCallerSecret = $parameterBag->get('SIP_CALLER_SECRET');
        $this->token = 'Bearer ' . $sipCallerSecret;
    }


    #[\Symfony\Component\Routing\Attribute\Route(path: '/api/v1/lobby/sip/room/{roomId}', name: 'caller_room', methods: ['GET'])]
    public
    function findRoom(Request $request, string $roomId): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }
        return new JsonResponse($this->callerRoomService->findRoom($roomId));
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: '/api/v1/lobby/sip/pin/{roomId}', name: 'caller_pin', methods: ['POST', 'GET'])]
    public
    function findPin(Request $request, string $roomId): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }
        $error = [];
        if (!$request->get('pin')) {
            $error['error'] = 'MISSING_ARGUMENT';
            $error['argument'][] = 'pin';
        }
        if (!$request->get('caller_id')) {
            $error['error'] = 'MISSING_ARGUMENT';
            $error['argument'][] = 'caller_id';
        }
        if (sizeof($error) > 0) {
            return new JsonResponse($error, \Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND);
        }
        $session = $this->callerPinService->createNewCallerSession($roomId, $request->get('pin'), $request->get('caller_id'), $request->get('is_video')?:false);
        if (!$session) {
            $res = [
                'auth_ok' => false,
                'links' => []
            ];
        } else {
            $res = [
                'auth_ok' => true,
                'links' => [
                    'session' => $this->generateUrl('caller_session', ['session_id' => $session->getSessionId()]),
                    'left' => $this->generateUrl('caller_left', ['session_id' => $session->getSessionId()])
                ]
            ];
        }
        return new JsonResponse($res);
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: '/api/v1/lobby/sip/session', name: 'caller_session', methods: ['GET'])]
    public
    function findSession(Request $request): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }
        $error = [];
        if (!$request->get('session_id')) {
            $error['error'] = 'MISSING_ARGUMENT';
            $error['argument'] = [];
            $error['argument'][] = 'session_id';
        }
        if (sizeof($error) > 0) {
            return new JsonResponse($error, \Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND);
        }

        $res = $this->callerSessionService->getSessionStatus($request->get('session_id'));
        return new JsonResponse($res);
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: '/api/v1/lobby/sip/session/left', name: 'caller_left', methods: ['GET'])]
    public
    function leftSession(Request $request): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }

        $error = [];
        if (!$request->get('session_id')) {
            $error['error'] = 'MISSING_ARGUMENT';
            $error['argument'] = [];
            $error['argument'][] = 'session_id';
        }
        if (sizeof($error) > 0) {
            return new JsonResponse($error, \Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['error' => $this->callerLeftService->callerLeft($request->get('session_id'))]);
    }
}

