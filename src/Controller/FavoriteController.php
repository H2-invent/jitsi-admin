<?php

namespace App\Controller;

use App\Entity\Rooms;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class FavoriteController extends AbstractController
{
    /**
     * @Route("/room/favorite/toggle", name="room_favorite_toogle")
     */
    public function index(Request $request,TranslatorInterface $translator): Response
    {
        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('uid' => $request->get('uid')));
        $user = $this->getUser();
        if (in_array($user, $room->getUser()->toArray())) {
            if (in_array($room, $this->getUser()->getFavorites()->toArray())) {
                $user->removeFavorite($room);
            } else {
                $user->addFavorite($room);
            }
        }else{
            return $this->redirectToRoute('dashboard',array('snack'=>$translator->trans('Fehler'),'color'=>'danger'));
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();
        return $this->redirectToRoute('dashboard');
    }
}
