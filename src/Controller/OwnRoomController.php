<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Form\Type\JoinMyRoomType;
use App\Form\Type\JoinViewType;
use App\Service\RoomService;
use Firebase\JWT\JWT;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class OwnRoomController extends AbstractController
{

    /**
     * @Route("/myRoom/start/{uid}", name="own_room_startPage")
     */
    public function index($uid, Request $request, RoomService $roomService, TranslatorInterface $translator): Response
    {
        $rooms = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('uid'=>$uid));
        if(!$rooms){
            return $this->redirectToRoute('join_index_no_slug',array('snack'=>$translator->trans('Konferenz nicht gefunden. Zugangsdaten erneut eingeben'),'color'=>'danger'));
        }
        $data = array();
        if ($this->getUser()) {
            $data['name'] = $this->getUser()->getFirstName() . ' ' . $this->getUser()->getLastName();
        } else {
            if ($request->cookies->get('name')) {
                $data['name'] = $request->cookies->get('name');
            }
        }
        $isModerator = false;
        if ($this->getUser() == $rooms->getModerator()) {
            $isModerator = true;
        }
        $form = $this->createForm(JoinMyRoomType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if ($form->get('joinApp')->isClicked()) {
                $type = 'a';
            } elseif ($form->get('joinBrowser')->isClicked()) {
                $type = 'b';
            }

            $url = $roomService->joinUrl($type, $rooms->getServer(), $rooms->getUid(), $data['name'], $isModerator);
            if ($isModerator){
                if($this->getUser() == $rooms->getModerator() && $rooms->getPersistantRoom()){
                    $rooms->setStart(new \DateTime());
                    if($rooms->getTotalOpenRoomsOpenTime()){
                        $rooms->setEnddate((new \DateTime())->modify('+ '.$rooms->getTotalOpenRoomsOpenTime().' min'));
                    }
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($rooms);
                    $em->flush();
                }
                $res = $this->redirect($url);
                return  $res;
            }else{
                $res = $this->redirectToRoute('room_waiting',array('name'=>$data['name'],'uid'=>$rooms->getUid(),'type'=>$type));
            }
            $res->headers->setCookie(new Cookie('name', $data['name'], (new \DateTime())->modify('+365 days')));
            return $res;

        }

        return $this->render('own_room/index.html.twig', [
            'room'=>$rooms,
            'server' => $rooms->getServer(),
            'form' => $form->createView()
        ]);
    }
    /**
     * @Route("/mywaiting/waiting", name="room_waiting")
     */
    public function waiting(Request $request): Response
    {
        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('uid'=>$request->get('uid')));
        $name = $request->get('name');
        $type = $request->get('type');
        return $this->render('own_room/waiting.html.twig', [
            'room'=>$room,
            'server'=>$room->getServer(),
            'name'=>$name,
            'type'=>$type
        ]);
    }
    /**
     * @Route("/room/enterLink/{uid}", name="room_enter_link")
     * @ParamConverter("rooms", options={"mapping": {"uid": "uid"}})
     */
    public function link(Rooms $rooms, Request $request): Response
    {
       if($rooms->getModerator()!= $this->getUser()){
           throw new NotFoundHttpException('Room not Found');
       }

        return $this->render('own_room/__enterLinkModal.html.twig', [
            'room' => $rooms,
        ]);
    }
    /**
     * @Route("/mywaiting/check/{uid}/{name}/{type}", name="room_waiting_check")
     * @ParamConverter("rooms", options={"mapping": {"uid": "uid"}})
     */
    public function checkWaiting(Rooms $rooms,$name,$type, Request $request,RoomService $roomService): Response
    {
        $now = new \DateTime();

        if(($rooms->getStart()< $now && $rooms->getEnddate() > $now) || ($rooms->getTotalOpenRoomsOpenTime() === null && $rooms->getPersistantRoom() === true)){
            return new JsonResponse(array('error'=>false,'url'=>$roomService->joinUrl($type,$rooms->getServer(),$rooms->getUid(),$name,false)));
        }else{
            return new JsonResponse(array('error'=>true));
        }
    }
}
