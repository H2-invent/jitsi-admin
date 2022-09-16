<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Helper\JitsiAdminController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/room/callout/', name: 'sip_call_out_')]
class SipCallOutController extends JitsiAdminController
{
    #[Route('modal/{roomUid}', name: 'modal')]
    public function index($roomUid): Response
    {
        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(array('uidReal'=>$roomUid));
        dump($room);
        return $this->render('sip_call_out/inviteModal.html.twig', [
            'title' => 'Teilnehmer einladen',
        ]);
    }
}
