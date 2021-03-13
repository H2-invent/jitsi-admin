<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\User;
use App\Service\PermissionChangeService;
use App\Service\RoomService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ChangePermissionsController extends AbstractController
{
    /**
     * @Route("/room/change/permissions/shareScreen", name="change_permissions_screenShare")
     */
    public function shareScreen(Request $request, TranslatorInterface $translator, PermissionChangeService $permissionChangeService): Response
    {
        $room = $this->getDoctrine()->getRepository(Rooms::class)->find($request->get('room'));
        if(!$room){
            return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.')]);
        }
        $userNew = $this->getDoctrine()->getRepository(User::class)->find($request->get('user'));
        if(!$userNew){
            return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.')]);
        }
        $userOld = $room->getModerator();
        if($permissionChangeService->toggleShareScreen($userOld,$userNew,$room)){
            return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Der Moderator wurde erfolgreich hinzugefügt')]);
        }
        return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.')]);
    }
    /**
     * @Route("/room/change/permissions/privateMessage", name="change_permissions_privateMessage")
     */
    public function privateMesage(Request $request, TranslatorInterface $translator, PermissionChangeService $permissionChangeService): Response
    {
        $room = $this->getDoctrine()->getRepository(Rooms::class)->find($request->get('room'));
        if(!$room){
            return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.')]);
        }
        $userNew = $this->getDoctrine()->getRepository(User::class)->find($request->get('user'));
        if(!$userNew){
            return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.')]);
        }
        $userOld = $room->getModerator();
        if($permissionChangeService->togglePrivateMessage($userOld,$userNew,$room)){
            return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Der Moderator wurde erfolgreich hinzugefügt')]);
        }
        return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.')]);
    }
    /**
     * @Route("/room/addModerator", name="room_add_moderator")
     */
    public function roomTransferModerator(Request $request, PermissionChangeService $permissionChangeService, TranslatorInterface $translator)
    {
        $room = $this->getDoctrine()->getRepository(Rooms::class)->find($request->get('room'));
        if(!$room){
            return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.')]);
        }
        $userNew = $this->getDoctrine()->getRepository(User::class)->find($request->get('user'));
        if(!$userNew){
            return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.')]);
        }
        $userOld = $room->getModerator();
        if($permissionChangeService->toggleModerator($userOld,$userNew,$room)){
            return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Der Moderator wurde erfolgreich hinzugefügt')]);
        }
        return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.')]);
    }
}
