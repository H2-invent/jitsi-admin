<?php

namespace App\Controller\api;

use App\Helper\JitsiAdminController;
use App\Service\api\CheckAuthorizationService;
use App\Service\caller\CallerFindRoomService;
use App\Service\caller\CallerLeftService;
use App\Service\caller\CallerPinService;
use App\Service\caller\CallerSessionService;
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
    private $token;
    private $callerRoomService;
    private $callerPinService;
    private $callerSessionService;
    private $callerLeftService;

    public function __construct(ManagerRegistry       $managerRegistry,
                                TranslatorInterface   $translator,
                                LoggerInterface       $logger,
                                ParameterBagInterface $parameterBag,
                                CallerLeftService     $callerLeftService,
                                CallerSessionService  $callerSessionService,
                                CallerPinService      $callerPinService,
                                CallerFindRoomService $callerFindRoomService
    )
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
        $this->callerRoomService = $callerFindRoomService;
        $this->callerPinService = $callerPinService;
        $this->callerSessionService = $callerSessionService;
        $this->callerLeftService = $callerLeftService;
        $this->token = 'Bearer ' . $parameterBag->get('SIP_CALLER_SECRET');
    }

    /**
     * @Route("/api/v1/lobby/sip/room/{roomId}", name="caller_room",methods={"GET"})
     */
    public function findRoom(Request $request, $roomId): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }
        return new JsonResponse($this->callerRoomService->findRoom($roomId));
    }

    /**
     * @Route("/api/v1/lobby/sip/pin/{roomId}", name="caller_pin",methods={"POST"})
     */
    public function findPin(Request $request, $roomId): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }
        $error = array();
        if (!$request->get('pin')) {
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
        $session = $this->callerPinService->createNewCallerSession($roomId, $request->get('pin'), $request->get('caller_id'));
        if (!$session) {
            $res = array(
                'auth_ok' => false,
                'links' => array()
            );
        } else {
            $res = array(
                'auth_ok' => true,
                'links' => array(
                    'session' => $this->generateUrl('caller_session', array('session_id' => $session->getSessionId())),
                    'left' => $this->generateUrl('caller_left', array('session_id' => $session->getSessionId()))
                )
            );
        }
        return new JsonResponse($res);
    }

    /**
     * @Route("/api/v1/lobby/sip/session", name="caller_session",methods={"GET"})
     */
    public function findSession(Request $request): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }
        $error = array();
        if (!$request->get('session_id')) {
            $error['error'] = 'MISSING_ARGUMENT';
            $error['argument'] = array();
            $error['argument'][] = 'session_id';
        }
        if (sizeof($error) > 0) {
            return new JsonResponse($error, 404);
        }

        $res = $this->callerSessionService->getSessionStatus($request->get('session_id'));
        return new JsonResponse($res);
    }

    /**
     * @Route("/api/v1/lobby/sip/session/left", name="caller_left",methods={"GET"})
     */
    public function leftSession(Request $request): Response
    {
        $check = CheckAuthorizationService::checkHEader($request, $this->token);
        if ($check) {
            return $check;
        }

        $error = array();
        if (!$request->get('session_id')) {
            $error['error'] = 'MISSING_ARGUMENT';
            $error['argument'] = array();
            $error['argument'][] = 'session_id';
        }
        if (sizeof($error) > 0) {
            return new JsonResponse($error, 404);
        }

        return new JsonResponse(array('error' => $this->callerLeftService->callerLeft($request->get('session_id'))));
    }


}
