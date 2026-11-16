<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\Tag;
use App\Entity\User;
use App\Helper\JitsiAdminController;
use App\Service\AdHocMeeting\AdhocCallService;
use App\Service\AdHocMeeting\AdhocMeetingService;
use App\Service\CreateHttpsUrl;
use App\Service\OnlineStatus\OnlineStatusService;
use App\Service\OnlineStatus\PresenceService;
use App\Service\ServerUserManagment;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\Symfony\Component\Routing\Attribute\Route(path: '/room/adhoc/', name: 'add_hoc')]
class AdHocMeetingController extends JitsiAdminController
{
    public function __construct(
        ManagerRegistry                 $managerRegistry,
        TranslatorInterface             $translator,
        LoggerInterface                 $logger,
        ParameterBagInterface           $parameterBag,
        private readonly CreateHttpsUrl $createHttpsUrl,
    ) {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: 'confirmation/{userId}/{serverId}', name: '_confirm')]
    public function confirmation(
        #[MapEntity(id: 'userId')] User     $user,
        #[MapEntity(id: 'serverId')] Server $server,
    ): Response {
        $tag = $server->getTag();
        return $this->render('add_hoc_meeting/__confirmation.html.twig', ['server' => $server, 'user' => $user, 'tag' => $tag]);
    }

    /**
     * The callee refused the ringing ad-hoc call. Removes the waiting callout session and tells
     * the caller right away, instead of letting the caller wait for the signaling timeout.
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: 'decline/{roomId}', name: '_decline')]
    public function decline(
        #[MapEntity(id: 'roomId')] Rooms $room,
        AdhocCallService                $adhocCallService,
    ): JsonResponse {
        $declined = $adhocCallService->markDeclined($this->getUser(), $room);

        return new JsonResponse(['status' => $declined ? 'DECLINED' : 'NO_ACTIVE_CALL']);
    }

    /**
     * The caller stopped the attempt (left/ended the conference) before the callee answered.
     * Cancels all still ringing calls started by the current user so the callee stops ringing.
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: 'cancel', name: '_cancel')]
    public function cancel(AdhocCallService $adhocCallService): JsonResponse
    {
        $cancelled = $adhocCallService->cancelPendingCallsByInviter($this->getUser());

        return new JsonResponse(['cancelled' => $cancelled]);
    }

    /**
     * The callee's ringing dialog reached the configured signaling duration without an answer.
     * Marks the call as timed out and tells the caller, independent of the messenger worker.
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: 'timeout/{roomId}', name: '_timeout')]
    public function timeout(
        #[MapEntity(id: 'roomId')] Rooms $room,
        AdhocCallService                $adhocCallService,
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['status' => 'UNAUTHORIZED'], Response::HTTP_UNAUTHORIZED);
        }

        $timedOut = $adhocCallService->markTimedOut($user, $room);

        return new JsonResponse(['status' => $timedOut ? 'NO_ANSWER' : 'ANSWERED']);
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: 'meeting/{userId}/{serverId}/{tagId}', name: '_meeting')]
    #[\Symfony\Component\Routing\Attribute\Route(path: 'meeting/{userId}/{serverId}', name: '_meeting_no_tag')]
    public function index(
        #[MapEntity(id: 'userId')] User     $user,
        #[MapEntity(id: 'serverId')] Server $server,
        TranslatorInterface                 $translator,
        ServerUserManagment                 $serverUserManagment,
        AdhocMeetingService                 $adhocMeetingService,
        OnlineStatusService                 $onlineStatusService,
        PresenceService                     $presenceService,
        #[MapEntity(id: 'tagId')] ?Tag      $tag = null,
    ): Response {
        if (!in_array($user, $this->getUser()->getAddressbook()->toArray())) {
            $this->addFlash('danger', $translator->trans('Fehler, Der User wurde nicht gefunden'));
            return new JsonResponse(['redirectUrl' => $this->generateUrl('dashboard')]);
        }

        $servers = $serverUserManagment->getServersFromUser($this->getUser());

        if (!in_array($server, $servers)) {
            $this->addFlash('danger', $translator->trans('Fehler, Der Server wurde nicht gefunden'));
            return new JsonResponse(['redirectUrl' => $this->generateUrl('dashboard')]);
        }

        // Live websocket presence is authoritative: an active connection (including a Chrome
        // extension socket) means callable, a definite "offline" means not. The stored status is
        // only the fallback when presence cannot be determined (null, e.g. websocket service
        // unreachable) so a presence outage does not block or allow calls by accident.
        $presence = $presenceService->isUserOnline($user);
        if ($presence === false || ($presence === null && !$onlineStatusService->isUserOnline($user))) {
            return new JsonResponse(
                [
                    'redirectUrl' => $this->generateUrl('dashboard'),
                    'error'       => $translator->trans('addhock.notification.offline'),
                ]
            );
        }

        try {
            $room = $adhocMeetingService->createAdhocMeeting($this->getUser(), $user, $server, $tag);

            if ($server->shouldProvisionNewServer()) {
                return new JsonResponse(
                    [
                        'redirectUrl' => $this->generateUrl('app_provisioner_create', ['uidReal' => $room->getUidReal()]),
                    ]
                );
            }
            return new JsonResponse(
                [
                    'redirectUrl' => $this->generateUrl('dashboard'),
                    'popups'      => [
                        ['url'   => $this->createHttpsUrl->createHttpsUrl($this->generateUrl('room_join', ['type' => 'b', 'room' => $room->getId()])),
                         'title' => $room->getSecondaryName() ?: $room->getName()
                        ]
                    ]
                ]
            );
        } catch (\Exception $exception) {
            $this->logger->error($exception);
            $this->addFlash('danger', $translator->trans('Fehler'));
            return new JsonResponse(['redirectUrl' => $this->generateUrl('dashboard')]);
        }
    }
}
