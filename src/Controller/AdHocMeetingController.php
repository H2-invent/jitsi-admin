<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\Tag;
use App\Entity\User;
use App\Helper\JitsiAdminController;
use App\Service\adhocmeeting\AdhocMeetingService;
use App\Service\Lobby\DirectSendService;
use App\Service\RoomGeneratorService;
use App\Service\ServerUserManagment;
use App\Service\TimeZoneService;
use App\Service\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/room/adhoc/", name="add_hoc")
 */
class AdHocMeetingController extends JitsiAdminController
{


    /**
     * @Route("confirmation/{userId}/{serverId}", name="_confirm")
     * @ParamConverter("user", class="App\Entity\User",options={"mapping": {"userId": "id"}})
     * @ParamConverter("server", class="App\Entity\Server",options={"mapping": {"serverId": "id"}})
     */
    public function confirmation(
        User                $user,
        Server              $server,
        TranslatorInterface $translator,
        ServerUserManagment $serverUserManagment,
        AdhocMeetingService $adhocMeetingService
    ): Response
    {
        $tag = $this->doctrine->getRepository(Tag::class)->findBy(array('disabled'=>false),array('priority'=>'ASC'));
        return $this->render('add_hoc_meeting/__confirmation.html.twig', array('server' => $server, 'user' => $user, 'tag' =>$tag));
    }

    /**
     * @Route("meeting/{userId}/{serverId}/{tagId}", name="_meeting")
     * @Route("meeting/{userId}/{serverId}", name="_meeting_no_tag")
     * @ParamConverter("user", class="App\Entity\User",options={"mapping": {"userId": "id"}})
     * @ParamConverter("server", class="App\Entity\Server",options={"mapping": {"serverId": "id"}})
     * @ParamConverter("tag", class="App\Entity\Tag",options={"mapping": {"tagId": "id"}})
     */
    public function index(
        User                $user,
        Server              $server,
        TranslatorInterface $translator,
        ServerUserManagment $serverUserManagment,
        AdhocMeetingService $adhocMeetingService,
        ?Tag                 $tag = null
    ): Response
    {

        if (!in_array($user, $this->getUser()->getAddressbook()->toArray())) {
            $this->addFlash('danger', $translator->trans('Fehler, Der User wurde nicht gefunden'));
            return new JsonResponse(array('redirectUrl' => $this->generateUrl('dashboard')));

        }

        $servers = $serverUserManagment->getServersFromUser($this->getUser());

        if (!in_array($server, $servers)) {
            $this->addFlash('danger', $translator->trans('Fehler, Der Server wurde nicht gefunden'));
            return new JsonResponse(array('redirectUrl' => $this->generateUrl('dashboard')));

        }
        try {
            $room = $adhocMeetingService->createAdhocMeeting($this->getUser(), $user, $server,$tag);
            // $this->addFlash('_blank',$this->generateUrl('room_join',array('t'=>'b','room'=>$room->getId())));
            $this->addFlash('success', $translator->trans('Konferenz erfolgreich erstellt'));
            return new JsonResponse(array(
                    'redirectUrl' => $this->generateUrl('dashboard'),
                    'popups' => array(
                        $this->generateUrl('room_join', array('t' => 'b', 'room' => $room->getId()))
                    )
                )
            );

        } catch (\Exception $exception) {
            $this->addFlash('danger', $translator->trans('Fehler'));
            return new JsonResponse(array('redirectUrl' => $this->generateUrl('dashboard')));

        }
    }
}
