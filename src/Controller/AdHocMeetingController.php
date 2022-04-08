<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Helper\JitsiAdminController;
use App\Service\RoomGeneratorService;
use App\Service\RoomService;
use App\Service\ServerUserManagment;
use App\Service\ThemeService;
use App\Service\TimeZoneService;
use App\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdHocMeetingController extends JitsiAdminController
{

    /**
     * @Route("/room/adhoc/meeting/{userId}/{serverId}", name="add_hoc_meeting")
     * @ParamConverter("user", class="App\Entity\User",options={"mapping": {"userId": "id"}})
     * @ParamConverter("server", class="App\Entity\Server",options={"mapping": {"serverId": "id"}})
     */
    public function index(RoomGeneratorService $roomGeneratorService, ParameterBagInterface $parameterBag, ThemeService $themeService, User $user, Server $server, UserService $userService,TranslatorInterface $translator, ServerUserManagment $serverUserManagment): Response
    {

        if(!in_array($user,$this->getUser()->getAddressbook()->toArray())){
            return $this->redirectToRoute('dashboard',array('snack'=>$translator->trans('Fehler, Der User wurde nicht gefunden')));
        }
        $servers = $serverUserManagment->getServersFromUser($this->getUser());

        if(!in_array($server,$servers)){
            return $this->redirectToRoute('dashboard',array('color'=>'danger','snack'=>$translator->trans('Fehler, Der Server wurde nicht gefunden')));
        }
        $room = $roomGeneratorService->createRoom($this->getUser(),$server);

        $now = new \DateTime('now',TimeZoneService::getTimeZone($this->getUser()));
        $room->setStart($now);
        if ($parameterBag->get('allowTimeZoneSwitch') == 1){
            $room->setTimeZone($this->getUser()->getTimeZone());
        }
        $room->setEnddate((clone $now)->modify('+ 1 hour'));
        $room->setDuration(60);
        $room->setName($translator->trans('Konferenz mit {n}',array('{n}'=>$user->getFormatedName($parameterBag->get('laf_showName')))));
        $em = $this->doctrine->getManager();
        $em->persist($room);
        $em->flush();
        $user->addRoom($room);
        $em->persist($user);
        $this->getUser()->addRoom($room);
        $em->persist($this->getUser());
        $em->flush();
        $userService->addUser($user,$room);
        $userService->addUser($this->getUser(),$room);
        return $this->redirectToRoute('dashboard',array('snack'=>$translator->trans('Konferenz erfolgreich erstellt')));
    }
}
