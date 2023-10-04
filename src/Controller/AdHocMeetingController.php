<?php

namespace App\Controller;

use App\Entity\Server;
use App\Entity\Tag;
use App\Entity\User;
use App\Helper\JitsiAdminController;
use App\Service\adhocmeeting\AdhocMeetingService;
use App\Service\CreateHttpsUrl;
use App\Service\ServerUserManagment;
use Doctrine\Persistence\ManagerRegistry;
use GuzzleHttp\Promise\Create;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/room/adhoc/", name="add_hoc")
 */
class AdHocMeetingController extends JitsiAdminController
{
    public function __construct(
        ManagerRegistry $managerRegistry,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        ParameterBagInterface $parameterBag,
        private CreateHttpsUrl $createHttpsUrl,
    )
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
    }

    /**
     * @Route("confirmation/{userId}/{serverId}", name="_confirm")
     */
    public function confirmation(
        #[MapEntity(id: 'userId')] User   $user,
        #[MapEntity(id: 'serverId')] Server $server,
    ): Response
    {
        $tag = $server->getTag();
        return $this->render('add_hoc_meeting/__confirmation.html.twig', ['server' => $server, 'user' => $user, 'tag' => $tag]);
    }

    /**
     * @Route("meeting/{userId}/{serverId}/{tagId}", name="_meeting")
     * @Route("meeting/{userId}/{serverId}", name="_meeting_no_tag")
     */
    public function index(
        #[MapEntity(id: 'userId')] User                $user,
        #[MapEntity(id: 'serverId')] Server              $server,
        TranslatorInterface $translator,
        ServerUserManagment $serverUserManagment,
        AdhocMeetingService $adhocMeetingService,
        #[MapEntity(id: 'tagId')] ?Tag $tag = null,

    ): Response
    {

        if (!in_array($user, $this->getUser()->getAddressbook()->toArray())) {
            $this->addFlash('danger', $translator->trans('Fehler, Der User wurde nicht gefunden'));
            return new JsonResponse(['redirectUrl' => $this->generateUrl('dashboard')]);
        }

        $servers = $serverUserManagment->getServersFromUser($this->getUser());

        if (!in_array($server, $servers)) {
            $this->addFlash('danger', $translator->trans('Fehler, Der Server wurde nicht gefunden'));
            return new JsonResponse(['redirectUrl' => $this->generateUrl('dashboard')]);
        }
        try {
            $room = $adhocMeetingService->createAdhocMeeting($this->getUser(), $user, $server, $tag);
            return new JsonResponse(
                [
                    'redirectUrl' => $this->generateUrl('dashboard'),
                    'popups' => [
                        ['url' => $this->createHttpsUrl->createHttpsUrl($this->generateUrl('room_join', ['t' => 'b', 'room' => $room->getId()])), 'title' => $room->getSecondaryName() ? : $room->getName()]
                    ]
                ]
            );
        } catch (\Exception $exception) {
            $this->addFlash('danger', $translator->trans('Fehler'));
            return new JsonResponse(['redirectUrl' => $this->generateUrl('dashboard')]);
        }
    }
}
