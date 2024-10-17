<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Helper\JitsiAdminController;
use App\Service\FavoriteService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class FavoriteController extends JitsiAdminController
{
    #[Route(path: '/room/favorite/toggle', name: 'room_favorite_toogle')]
    public function index(Request $request, TranslatorInterface $translator, FavoriteService $favoriteService): Response
    {
        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(['uidReal' => $request->get('uid')]);
        $user = $this->getUser();

        if (in_array($user, $room->getUser()->toArray())) {
            if (in_array($room, $this->getUser()->getFavorites()->toArray())) {
                $user->removeFavorite($room);
            } else {
                $user->addFavorite($room);
            }
        } else {
            $this->addFlash('danger', $translator->trans('Fehler'));
            return $this->redirectToRoute('dashboard');
        }
        $em = $this->doctrine->getManager();
        $em->persist($user);
        $em->flush();
        return $this->redirectToRoute('dashboard');
    }
}
