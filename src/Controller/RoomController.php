<?php

namespace App\Controller;

use App\Entity\Log;
use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Entity\Scheduling;
use App\Entity\Server;
use App\Entity\User;
use App\Form\Type\NewMemberType;
use App\Form\Type\RoomType;
use App\Helper\JitsiAdminController;
use App\Service\NewRoomService;
use App\Service\PermissionChangeService;
use App\Service\RemoveRoomService;
use App\Service\RepeaterService;
use App\Service\RoomAddService;
use App\Service\RoomCheckService;
use App\Service\RoomGeneratorService;
use App\Service\SchedulingService;
use App\Service\ServerService;
use App\Service\ServerUserManagment;
use App\Service\ThemeService;
use App\Service\UserService;
use App\Service\InviteService;

use App\Service\RoomService;
use App\UtilsHelper;
use phpDocumentor\Reflection\Types\False_;
use phpDocumentor\Reflection\Types\This;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RoomController extends JitsiAdminController
{


    /**
     * @Route("/room/new", name="room_new")
     */
    public function newRoom(
        RoomGeneratorService $roomGeneratorService,
        SchedulingService    $schedulingService,
        Request              $request,
        UserService          $userService,
        TranslatorInterface  $translator,
        ServerUserManagment  $serverUserManagment,
        RoomCheckService     $roomCheckService,
        SerializerInterface  $serializer,
        NewRoomService       $newRoomService,
    )
    {
        $room = $newRoomService->newRoomService(request: $request,myUser: $this->getUser());
        if ($room instanceof Response){
            return $room;
        }
        $servers = $serverUserManagment->getServersFromUser($this->getUser());

        $id = $request->get('id') ?? null;
        $edit = ($id !== null);
        $snack = $translator->trans('Terminplanung erfolgreich erstellt');
        $title = $edit?$translator->trans('Konferenz bearbeiten'):$translator->trans('Neue Konferenz erstellen');

        $roomold = clone $room;

        $form = $this->createForm(RoomType::class,
            $room, [
                'user' => $this->getUser(),
                'server' => $servers,
                'action' => $this->generateUrl('room_new',
                    [
                        'id' => $room->getId()]
                ),
                'isEdit' => (bool)$edit
            ]
        );
        $form->remove('scheduleMeeting');

        if ($edit) {
            $form->remove('moderator');
            if (!in_array($room->getServer(), $servers)) {
                $form->remove('server');
            }
        }

        try {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {

                $room = $form->getData();

                $error = array();
                $room = $roomCheckService->checkRoom($room, $error);
                if (sizeof($error) > 0) {
                    return new JsonResponse(array('error' => true, 'messages' => $error));
                }
                if ($room->getPersistantRoom()) {
                    $room->setStart(null);
                    $room->setStartUtc(null);
                    $room->setStartTimestamp(null);
                    $room->setEnddate(null);
                    $room->setEndDateUtc(null);
                    $room->setEndTimestamp(null);
                }
                if (!in_array($room->getTag(), $room->getServer()->getTag()->toArray())) {
                    $room->setTag(null);
                }
                if ($room->getServer()->getTag()->count() === 1) {
                    $room->setTag($room->getServer()->getTag()->first());
                }
                $em = $this->doctrine->getManager();
                $em->persist($room);
                $em->flush();
                $schedulingService->createScheduling($room);

                if ($edit) {
                    if ($newRoomService->roomChanged($roomold, $room)) {
                        foreach ($room->getUser() as $user) {
                            $userService->editRoom($user, $room);
                        }
                    }
                    $this->addFlash('success', $translator->trans('Konferenz erfolgreich bearbeitet'));
                    $newRoomService->writeLogInDatabase(roomold: $roomold,room: $room, myUser: $this->getUser());
                } else {
                    $roomGeneratorService->addUserToRoom($room->getModerator(), $room, true);
                    $moderator = $room->getModerator();
                    $userService->addUser($moderator, $room);
                    $this->addFlash('success', $translator->trans('Konferenz erfolgreich erstellt'));
                }

                $modalUrl = base64_encode($this->generateUrl('room_add_user', array('room' => $room->getId())));
                if ($room->getScheduleMeeting()) {
                    $modalUrl = base64_encode($this->generateUrl('schedule_admin', array('id' => $room->getId())));
                }

                $this->addFlash('modalUrl', $modalUrl);
                $res = $this->generateUrl('dashboard');

                return new JsonResponse(array('error' => false, 'redirectUrl' => $res, 'cookie' => array('room_server' => $room->getServer()->getId())));

            } elseif ($form->isSubmitted() && !$form->isValid()) {

                return new JsonResponse(array('error' => true, 'messages' => [$translator->trans('Fehler, Bitte Laden Sie die Seite neu')]));

            }

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->addFlash('danger', 'Fehler, Bitte kontrollieren Sie ihre Daten.');
            $res = $this->generateUrl('dashboard');
            return new JsonResponse(array('error' => false, 'redirectUrl' => $res));
        }
        return $this->render('base/__newRoomModal.html.twig', array('isEdit' => $edit, 'server' => $servers, 'serverchoose' => $room->getServer(), 'form' => $form->createView(), 'title' => $title));
    }


    /**
     * @Route("/room/remove", name="room_remove")
     */
    public
    function roomRemove(Request $request, RepeaterService $repeaterService, RemoveRoomService $removeRoomService)
    {

        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(['id' => $request->get('room')]);
        $color = 'danger';
        $snack = 'Keine Berechtigung';
        if (UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $room)) {
            if ($room->getRepeater()) {
                $repeater = $room->getRepeater();
                $repeaterService->sendEMail($repeater, 'email/repeaterEdit.html.twig', $this->translator->trans('Die Serienvideokonferenz {name} wurde bearbeitet', array('{name}' => $repeater->getPrototyp()->getName())), array('room' => $repeater->getPrototyp()));
                $room->setRepeater(null);
            }
            if ($removeRoomService->deleteRoom($room)) {
                $snack = $this->translator->trans('Konferenz gelöscht');
                $color = 'success';
            } else {
                $snack = $this->translator->trans('Fehler, Bitte Laden Sie die Seite neu');
            }
        }
        $this->addFlash($color, $snack);
        return $this->redirectToRoute('dashboard');
    }

    /**
     * @Route("/room/clone/{room}", name="room_clone")
     */
    public
    function roomClone($room, RoomGeneratorService $roomGeneratorService, RoomCheckService $roomCheckService, Request $request, UserService $userService, TranslatorInterface $translator, SchedulingService $schedulingService, ServerUserManagment $serverUserManagment)
    {

        $roomOld = $this->doctrine->getRepository(Rooms::class)->find($room);
        $edit = true;
        $roomNew = clone $roomOld;
        $roomNew->setStart(null);
        $roomNew->setStartUtc(null);
        $roomNew->setStartTimestamp(null);
        $roomNew->setEnddate(null);
        $roomNew->setEndDateUtc(null);
        $roomNew->setEndTimestamp(null);
        $roomNew = $roomGeneratorService->createCallerId($roomNew);
        // here we clean all the scheduls from the old room
        foreach ($roomNew->getSchedulings() as $data) {
            $roomNew->removeScheduling($data);
        }
        $roomNew->setUid(rand(01, 99) . time());
        $roomNew->setSequence(0);

        $snack = $translator->trans('Keine Berechtigung');
        $title = $translator->trans('Konferenz duplizieren');

        if (UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $roomNew)) {

            $servers = $serverUserManagment->getServersFromUser($this->getUser());
            if ($request->get('serverfake')) {
                $tmp = $this->doctrine->getRepository(Server::class)->find($request->get('serverfake'));
                if ($tmp) {
                    $serverChhose = $tmp;
                    $roomNew->setServer($serverChhose);
                }
            }

            $form = $this->createForm(RoomType::class, $roomNew, ['server' => $servers, 'action' => $this->generateUrl('room_clone', ['room' => $roomNew->getId()])]);
            $form->remove('scheduleMeeting');
            $form->handleRequest($request);

            $serverChhose = $roomNew->getServer();

            if ($form->isSubmitted() && $form->isValid()) {
                $roomNew = $form->getData();
                foreach ($roomOld->getUserAttributes() as $data) {
                    $tmp = clone $data;
                    $roomNew->addUserAttribute($tmp);
                }
                $roomNew->setUidReal(md5(uniqid('h2-invent', true)));
                $roomNew->setUidModerator(md5(uniqid()));
                $roomNew->setUidParticipant(md5(uniqid()));
                $roomNew->setSequence(0);
                $roomNew->setUid(rand(0, 99) . time());
                $em = $this->doctrine->getManager();
                $error = array();
                $roomNew = $roomCheckService->checkRoom($roomNew, $error);
                if (sizeof($error) > 0) {
                    return new JsonResponse(array('error' => true, 'messages' => $error));
                }

                $em->persist($roomNew);
                $em->flush();

                $schedulingService->createScheduling($roomNew);
                foreach ($roomOld->getUser() as $user) {
                    $userService->addUser($user, $roomNew);
                }
                $snack = $translator->trans('Konferenz erfolgreich erstellt');
                $this->addFlash('success', $snack);
                $this->addFlash('modalUrl', base64_encode($this->generateUrl('room_add_user', array('room' => $roomNew->getId()))));
                $res = $this->generateUrl('dashboard');
                return new JsonResponse(array('error' => false, 'redirectUrl' => $res, 'cookie' => array('room_server' => $roomNew->getServer()->getId())));

            }
            return $this->render('base/__newRoomModal.html.twig', array('isEdit' => $edit, 'form' => $form->createView(), 'server' => $servers, 'serverchoose' => $serverChhose, 'title' => $title));
        }

        $snack = $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.');
        $this->addFlash('danger', $snack);
        $res = $this->generateUrl('dashboard');
        return new JsonResponse(array('error' => false, 'redirectUrl' => $res));
    }

}
