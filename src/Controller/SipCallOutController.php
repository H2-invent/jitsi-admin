<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Helper\JitsiAdminController;
use App\Service\Callout\CalloutService;
use App\Service\Lobby\ToModeratorWebsocketService;
use App\Service\RoomAddService;
use App\UtilsHelper;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\Symfony\Component\Routing\Attribute\Route('/room/callout/', name: 'sip_call_out_')]
class SipCallOutController extends JitsiAdminController
{
    public function __construct(
        ManagerRegistry                              $managerRegistry,
        TranslatorInterface                          $translator,
        LoggerInterface                              $logger,
        ParameterBagInterface                        $parameterBag,
        private readonly RoomAddService              $roomAddService,
        private readonly CalloutService              $calloutService,
        private readonly ToModeratorWebsocketService $toModeratorWebsocketService,
    ) {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
    }

    #[\Symfony\Component\Routing\Attribute\Route('invite/{roomUid}', name: 'invite', methods: 'POST')]
    public function invite(string $roomUid, Request $request): Response
    {
        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(['uidReal' => $roomUid]);
        if (!UtilsHelper::isAllowedToOrganizeLobby($this->getUser(), $room)) {
            throw new NotFoundHttpException('Room not found');
        }

        $falseEmails = [];
        $user        = $this->roomAddService->createUserFromUserUid($request->get('uid'), $falseEmails);
        if ($user) {
            $this->roomAddService->addUserOnlytoOneRoom($user, $room);

            $this->calloutService->initCalloutSession($room, $user, $this->getUser());
            $this->toModeratorWebsocketService->refreshLobbyByRoom($room);
        }

        return new JsonResponse(['error' => !(sizeof($falseEmails) === 0), 'falseEmails' => $falseEmails]);
    }
}
