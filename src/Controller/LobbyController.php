<?php

namespace App\Controller;

use App\Entity\Rooms;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class LobbyController extends AbstractController
{
    /**
     * @Route("/room/lobby/moderator/{uid}", name="lobby")
     */
    public function index(Request  $request, TranslatorInterface $translator,LoggerInterface $logger, $uid): Response
    {
        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('uid'=>$uid));
        if($room->getModerator() !== $this->getUser()){
            $logger->log('error','User trys to enter room which he is no moderator of',array('room'=>$room->getId(), 'user'=>$this->getUser()->getUserIdentifier()));
            return $this->redirectToRoute('dashboard',array('snack'=>$translator->trans('Fehler')));
        }



        return $this->render('lobby/index.html.twig', [
            'controller_name' => 'LobbyController',
        ]);
    }
}
