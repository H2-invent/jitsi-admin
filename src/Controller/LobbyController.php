<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Service\JoinUrlGeneratorService;
use App\Service\RoomService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class LobbyController extends AbstractController
{
    private $parameterBag;
    private $translator;
    private $logger;
    public function __construct(ParameterBagInterface $parameterBag, TranslatorInterface $translator, LoggerInterface $logger)
    {
        $this->parameterBag = $parameterBag;
        $this->translator = $translator;
        $this->logger = $logger;
    }

    /**
     * @Route("/room/lobby/moderator/{uid}", name="lobby_moderator")
     */
    public function index(Request $request, $uid): Response
    {
        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('uid'=>$uid));
        if($room->getModerator() !== $this->getUser()){
            $this->logger->log('error','User trys to enter room which he is no moderator of',array('room'=>$room->getId(), 'user'=>$this->getUser()->getUserIdentifier()));
            return $this->redirectToRoute('dashboard',array('snack'=>$this->translator->trans('Fehler'),'color'=>'danger'));
        }
        return $this->render('lobby/index.html.twig', [
            'room' => $room,
            'server'=>$room->getServer()
        ]);
    }
    /**
     * @Route("/room/start/moderator/{t}/{room}", name="lobby_moderator_start")
     */
    public function startMeeting(Request  $request, $room,$t, RoomService $roomService): Response
    {
        $roomL = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('id'=>$room));
        if($roomL->getModerator() !== $this->getUser()){
            $this->logger->log('error','User trys to enter room which he is no moderator of',array('room'=>$roomL->getId(), 'user'=>$this->getUser()->getUserIdentifier()));
            return $this->redirectToRoute('dashboard',array('snack'=>$this->translator->trans('Fehler'),'color'=>'danger'));
        }
        $url= $roomService->join($roomL, $this->getUser(), $t, $this->getUser()->getFormatedName($this->parameterBag->get('laf_showNameInConference')));
        return $this->redirect($url) ;
    }
}