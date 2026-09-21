<?php

namespace App\Controller\api;

use App\Entity\CallerRoom;
use App\Entity\CallerSession;
use App\Entity\Server;
use App\Helper\JitsiAdminController;
use App\Service\api\CheckAuthorizationService;
use App\Service\api\ConferenceMapperService;
use App\Service\caller\CallerFindRoomService;
use App\Service\caller\CallerLeftService;
use App\Service\caller\CallerPinService;
use App\Service\caller\CallerSessionService;
use App\Service\caller\JitsiComponentSelectorService;
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
    private $legacyToken;
    private $callerRoomService;
    private $callerPinService;
    private $callerSessionService;
    private $callerLeftService;

    public function __construct(
        ManagerRegistry                       $managerRegistry,
        TranslatorInterface                   $translator,
        LoggerInterface                       $logger,
        ParameterBagInterface                 $parameterBag,
        CallerLeftService                     $callerLeftService,
        CallerSessionService                  $callerSessionService,
        CallerPinService                      $callerPinService,
        CallerFindRoomService                 $callerFindRoomService,
        private JitsiComponentSelectorService $jitsiComponentSelectorService,
        private ConferenceMapperService       $conferenceMapperService,
        private CheckAuthorizationService     $checkAuthorizationService,
    )
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
        $this->callerRoomService = $callerFindRoomService;
        $this->callerPinService = $callerPinService;
        $this->callerSessionService = $callerSessionService;
        $this->callerLeftService = $callerLeftService;
        $this->legacyToken = $parameterBag->get('SIP_CALLER_SECRET');
    }

    public function setJitsiComponentSelectorService(JitsiComponentSelectorService $jitsiComponentSelectorService): void
    {
        $this->jitsiComponentSelectorService = $jitsiComponentSelectorService;
    }


    #[Route(path: '/api/v1/lobby/sip/room/{roomId}', name: 'caller_room', methods: ['GET'])]
    public
    function findRoom(Request $request, $roomId): Response
    {
        $check = $this->authorize($request, $this->serverFromRoomId($roomId));
        if ($check) {
            return $check;
        }
        return new JsonResponse($this->callerRoomService->findRoom($roomId));
    }

    /**
     * The /sip/pin/ path is deprecated in favour of /sip/protected/ and only kept so existing
     * asterisk configurations keep working. Follow the links returned by caller_room instead of
     * hardcoding paths.
     */
    #[Route(path: '/api/v1/lobby/sip/protected/{roomId}', name: 'caller_protected', methods: ['POST', 'GET'])]
    #[Route(path: '/api/v1/lobby/sip/pin/{roomId}', name: 'caller_pin', methods: ['POST', 'GET'])]
    public
    function findPin(Request $request, $roomId): Response
    {
        if (str_contains($request->getPathInfo(), '/lobby/sip/pin/')) {
            $this->logger->warning(
                'Deprecated: /api/v1/lobby/sip/pin/{roomId} was called. Use /api/v1/lobby/sip/protected/{roomId} instead.',
                ['roomId' => $roomId]
            );
        }

        $check = $this->authorize($request, $this->serverFromRoomId($roomId));
        if ($check) {
            return $check;
        }
        $error = [];
        $pinRequired = !$this->callerRoomFromRoomId($roomId)?->getRoom()?->getTotalOpenRooms();
        if ($pinRequired && !$request->get('pin')) {
            $error['error'] = 'MISSING_ARGUMENT';
            $error['argument'][] = 'pin';
        }
        if (!$request->get('caller_id')) {
            $error['error'] = 'MISSING_ARGUMENT';
            $error['argument'][] = 'caller_id';
        }
        if (sizeof($error) > 0) {
            return new JsonResponse($error, 404);
        }
        $session = $this->callerPinService->createNewCallerSession($roomId, $request->get('pin') ?: null, $request->get('caller_id'), $request->get('is_video')?:false);
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

    /**
     * Dial-in for lobby-free rooms. No personal PIN or caller session is required; returns the
     * same payload as the deprecated conference-mapper endpoint, including the LiveKit SIP trunk.
     */
    #[Route(path: '/api/v1/lobby/sip/open/{roomId}', name: 'caller_open', methods: ['POST', 'GET'])]
    public function openRoom(Request $request, $roomId): Response
    {
        return new JsonResponse(
            $this->conferenceMapperService->checkConference(
                callerRoom: $this->doctrine->getRepository(CallerRoom::class)->findOneBy(['callerId' => $roomId]),
                apiKey: $request->headers->get('Authorization'),
                callerId: $request->get('caller_id')
            )
        );
    }

    #[Route(path: '/api/v1/lobby/sip/session', name: 'caller_session', methods: ['GET'])]
    public
    function findSession(Request $request): Response
    {
        $check = $this->authorize($request, $this->serverFromSessionId($request->get('session_id')));
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
            return new JsonResponse($error, 404);
        }

        $res = $this->callerSessionService->getSessionStatus($request->get('session_id'));
        return new JsonResponse($res);
    }

    #[Route(path: '/api/v1/lobby/sip/session/left', name: 'caller_left', methods: ['GET'])]
    public
    function leftSession(Request $request): Response
    {
        $check = $this->authorize($request, $this->serverFromSessionId($request->get('session_id')));
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
            return new JsonResponse($error, 404);
        }

        return new JsonResponse(['error' => $this->callerLeftService->callerLeft($request->get('session_id'))]);
    }

    private function authorize(Request $request, ?Server $server): ?Response
    {
        return $this->checkAuthorizationService->checkServerAuthorization($request, $server, $this->legacyToken);
    }

    private function serverFromRoomId($roomId): ?Server
    {
        return $this->callerRoomFromRoomId($roomId)?->getRoom()?->getServer();
    }

    private function callerRoomFromRoomId($roomId): ?CallerRoom
    {
        return $this->doctrine->getRepository(CallerRoom::class)->findOneBy(['callerId' => $roomId]);
    }

    private function serverFromSessionId($sessionId): ?Server
    {
        if (!$sessionId) {
            return null;
        }
        $session = $this->doctrine->getRepository(CallerSession::class)->findOneBy(['sessionId' => $sessionId]);

        return $session?->getCaller()?->getRoom()?->getServer();
    }
}
