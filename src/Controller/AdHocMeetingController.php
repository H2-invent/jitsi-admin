<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Service\RoomService;
use App\Service\ServerUserManagment;
use App\Service\UserService;
use phpDocumentor\Reflection\Types\This;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdHocMeetingController extends AbstractController
{
    /**
     * @Route("/room/adhoc/meeting/{userId}/{serverId}", name="add_hoc_meeting")
     * @ParamConverter("user", class="App\Entity\User",options={"mapping": {"userId": "id"}})
     * @ParamConverter("server", class="App\Entity\Server",options={"mapping": {"serverId": "id"}})
     */
    public function index(User $user, Server $server, UserService $userService,TranslatorInterface $translator, ServerUserManagment $serverUserManagment): Response
    {

        if(!in_array($user,$this->getUser()->getAddressbook()->toArray())){
            return $this->redirectToRoute('dashboard',array('snack'=>$translator->trans('Fehler, Der User wurde nicht gefunden')));
        }
        $servers = $serverUserManagment->getServersFromUser($this->getUser());

        if(!in_array($server,$servers)){
            return $this->redirectToRoute('dashboard',array('color'=>'danger','snack'=>$translator->trans('Fehler, Der Server wurde nicht gefunden')));
        }
        $room = new Rooms();
        $room->setModerator($this->getUser());
        $room->setStart(new \DateTime());
        $room->setEnddate((new \DateTime())->modify('+ 1 hour'));
        $room->setDuration(60);
        $room->setSequence(0);
        $room->setUidReal(md5(uniqid()));
        $room->setUid(rand(01, 99) . time());
        $room->setServer($server);
        $room->setName($translator->trans('Konferenz mit {n}',array('{n}'=>$user->getEmail())));
        $room->setOnlyRegisteredUsers(false);
        $em = $this->getDoctrine()->getManager();
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
