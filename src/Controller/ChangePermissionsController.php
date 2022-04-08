<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\User;
use App\Helper\JitsiAdminController;
use App\Service\PermissionChangeService;
use App\Service\RoomService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ChangePermissionsController extends JitsiAdminController
{

    /**
     * @Route("/room/change/permissions/shareScreen", name="change_permissions_screenShare")
     */
    public function shareScreen(Request $request, TranslatorInterface $translator, PermissionChangeService $permissionChangeService): Response
    {
        $room = $this->doctrine->getRepository(Rooms::class)->find($request->get('room'));
        if (!$room) {
            return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.')]);
        }
        $userNew = $this->doctrine->getRepository(User::class)->find($request->get('user'));
        if (!$userNew) {
            return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.')]);
        }
        $userOld = $room->getModerator();
        if ($permissionChangeService->toggleShareScreen($userOld, $userNew, $room)) {
            return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Dieser Teilnehmer darf seinen Bildschirm teilen')]);
        }
        return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.')]);
    }

    /**
     * @Route("/room/change/permissions/privateMessage", name="change_permissions_privateMessage")
     */
    public function privateMesage(Request $request, TranslatorInterface $translator, PermissionChangeService $permissionChangeService): Response
    {
        $room = $this->doctrine->getRepository(Rooms::class)->find($request->get('room'));
        if (!$room) {
            return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.')]);
        }
        $userNew = $this->doctrine->getRepository(User::class)->find($request->get('user'));
        if (!$userNew) {
            return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.')]);
        }
        $userOld = $room->getModerator();
        if ($permissionChangeService->togglePrivateMessage($userOld, $userNew, $room)) {
            return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Dieser Teilnehmer darf private Nachrichten versenden')]);
        }
        return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.')]);
    }

    /**
     * @Route("/room/addModerator", name="room_add_moderator")
     */
    public function roomTransferModerator(Request $request, PermissionChangeService $permissionChangeService, TranslatorInterface $translator)
    {
        $room = $this->doctrine->getRepository(Rooms::class)->find($request->get('room'));
        if (!$room) {
            return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.')]);
        }
        $userNew = $this->doctrine->getRepository(User::class)->find($request->get('user'));
        if (!$userNew) {
            return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.')]);
        }
        $userOld = $room->getModerator();
        if ($permissionChangeService->toggleModerator($userOld, $userNew, $room)) {
            return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Der Moderator wurde erfolgreich hinzugefügt')]);
        }
        return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.')]);
    }

    /**
     * @Route("/room/change/lobbyModerator", name="room_add_lobby_moderator")
     */
    public function roomTransferLobbyModerator(Request $request, PermissionChangeService $permissionChangeService, TranslatorInterface $translator)
    {
        $room = $this->doctrine->getRepository(Rooms::class)->find($request->get('room'));
        if (!$room) {
            return new JsonResponse(array('snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.')));
        }
        $userNew = $this->doctrine->getRepository(User::class)->find($request->get('user'));
        if (!$userNew) {
            return new JsonResponse(array('snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.')));
        }
        $userOld = $room->getModerator();
        $roomUser = $permissionChangeService->toggleLobbyModerator($userOld, $userNew, $room);
        if ($roomUser) {
            if($roomUser->getLobbyModerator()){
                return new JsonResponse(array('error' => false));
            }else{
                return new JsonResponse(array('error' => false));
            }
        }
        return new JsonResponse(array('snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.')));
    }
}
