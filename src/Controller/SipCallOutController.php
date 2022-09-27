<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\User;
use App\Helper\JitsiAdminController;
use App\Service\Callout\CalloutService;
use App\Service\RoomAddService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/room/callout/', name: 'sip_call_out_')]
class SipCallOutController extends JitsiAdminController
{
    public function __construct(
        ManagerRegistry        $managerRegistry,
        TranslatorInterface    $translator,
        LoggerInterface        $logger,
        ParameterBagInterface  $parameterBag,
        private RoomAddService $roomAddService,
        private CalloutService $calloutService,

    )
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
    }

    #[Route('modal/{roomUid}', name: 'modal')]
    public function index($roomUid): Response
    {
        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(array('uidReal' => $roomUid));

        return $this->render('sip_call_out/inviteModal.html.twig', [
            'title' => 'Teilnehmer einladen',
        ]);
    }

    #[Route('invite/{roomUid}', name: 'invite', methods: 'POST')]
    public function invite($roomUid, Request $request): Response
    {
        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(array('uidReal' => $roomUid));
        if ($room->getModerator() !== $this->getUser()) {
            throw new NotFoundHttpException('Room not found');
        }
        $falseEmails = array();
        $user = $this->roomAddService->createUserFromUserUid($request->get('uid'), $room, $falseEmails);
        if ($user) {
            $this->calloutService->initCalloutSession($room, $user, $this->getUser());
            return new JsonResponse(array('error' => false, 'falseEmails' => json_encode($falseEmails)));
        }

        return new JsonResponse(array('error' => !(sizeof($falseEmails) === 0), 'falseEmails' => json_encode($falseEmails)));
    }

}
