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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
    public function newRoom(RoomGeneratorService $roomGeneratorService, SchedulingService $schedulingService, Request $request, UserService $userService, TranslatorInterface $translator, ServerUserManagment $serverUserManagment, RoomCheckService $roomCheckService, SerializerInterface $serializer)
    {
        $servers = $serverUserManagment->getServersFromUser($this->getUser());
        $edit = false;
        if ($request->get('id')) {
            $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(array('id' => $request->get('id')));
            if (!$room) {
                $this->addFlash('danger', $translator->trans('Fehler'));
                return $this->redirectToRoute('dashboard');
            }
            if (!UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $room)) {
                $this->addFlash('danger', $translator->trans('Keine Berechtigung'));
                return $this->redirectToRoute('dashboard');
            }


            $title = $translator->trans('Konferenz bearbeiten');
            $sequence = $room->getSequence() + 1;
            $room->setSequence($sequence);
            if (!$room->getUidModerator()) {
                $room->setUidModerator(md5(uniqid('h2-invent', true)));
            }
            if (!$room->getUidParticipant()) {
                $room->setUidParticipant(md5(uniqid('h2-invent', true)));
            }
            $edit = true;
            $serverChhose = $room->getServer();
        } else {
            $serverChhose = null;
            if ($request->cookies->has('room_server')) {
                $server = $this->doctrine->getRepository(Server::class)->find($request->cookies->get('room_server'));
                if ($server && in_array($server, $servers)) {
                    $serverChhose = $server;
                }
            }
            if (sizeof($servers) === 1) {
                $serverChhose = $servers[0];
            }
            //Here we create the new Room with all depedencies

            $room = $roomGeneratorService->createRoom($this->getUser(), $serverChhose);

            $title = $translator->trans('Neue Konferenz erstellen');
        }

        $roomold = clone $room;
        $form = $this->createForm(RoomType::class, $room, ['user' => $this->getUser(), 'server' => $servers, 'action' => $this->generateUrl('room_new', ['id' => $room->getId()],), 'isEdit' => (bool)$request->get('id')]);
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
            $em = $this->doctrine->getManager();
            $em->persist($room);
            $em->flush();
            $schedulingService->createScheduling($room);

            if ($request->get('id')) {
                if (
                    $roomold->getStart() !== $room->getStart()
                    || $roomold->getDuration() !== $room->getDuration()
                    || $roomold->getName() !== $room->getName()
                    || $roomold->getAgenda() !== $room->getAgenda()
                    || $roomold->getPersistantRoom() !== $room->getPersistantRoom()
                ) {
                    foreach ($room->getUser() as $user) {
                        $userService->editRoom($user, $room);
                    }
                }
            } else {
                $roomGeneratorService->addUserToRoom($room->getModerator(), $room, true);
                $moderator = $room->getModerator();
                $userService->addUser($moderator, $room);
            }

            $modalUrl = base64_encode($this->generateUrl('room_add_user', array('room' => $room->getId())));
            if ($room->getScheduleMeeting()) {
                $modalUrl = base64_encode($this->generateUrl('schedule_admin', array('id' => $room->getId())));
            }
            if ($edit) {
                $this->addFlash('success', $translator->trans('Konferenz erfolgreich bearbeitet'));
                if ($room->getCreator() !== $room->getModerator()) {
                    $log = new Log();
                    $exclude = array(
                        'user',
                        'server',
                        'userAttributes',
                        'subscribers',
                        'schedulings',
                        'waitinglists',
                        'repeater',
                        'repeaterProtoype',
                        'favoriteUsers',
                        'lobbyWaitungUsers',
                        'roomstatuses',
                        'callerRoom',
                        'callerIds',
                        'tag',
                        'creator',
                        'logs');
                    $message = array(
                        'roomId' => $room->getId(),
                        'userName' => $this->getUser()->getUid(),
                        'state' => 'room Edit',
                        'oldObject' => json_decode($serializer->serialize($roomold,
                            JsonEncoder::FORMAT,
                            [AbstractNormalizer::IGNORED_ATTRIBUTES => $exclude])),
                        'newObject' => json_decode($serializer->serialize($room,
                            JsonEncoder::FORMAT,
                            [AbstractNormalizer::IGNORED_ATTRIBUTES => $exclude])),
                    );
                    $log->setCreatedAt(new \DateTime())
                        ->setUserName($this->getUser()->getUid())
                        ->setMessage(json_encode($message))
                        ->setUser($this->getUser())
                        ->setRoom($room);
                    $em = $this->doctrine->getManager();
                    $em->persist($log);
                    $em->flush();
                }
            } else {
                $this->addFlash('success', $translator->trans('Konferenz erfolgreich erstellt'));
            }
            $this->addFlash('modalUrl', $modalUrl);
            $res = $this->generateUrl('dashboard');

            return new JsonResponse(array('error' => false, 'redirectUrl' => $res, 'cookie' => array('room_server' => $room->getServer()->getId())));

            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->addFlash('danger', 'Fehler, Bitte kontrollieren Sie ihre Daten.');
            $res = $this->generateUrl('dashboard');
            return new JsonResponse(array('error' => false, 'redirectUrl' => $res));
        }
        return $this->render('base/__newRoomModal.html.twig', array('server' => $servers,'serverchoose'=>$serverChhose, 'form' => $form->createView(), 'title' => $title));
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
     * @Route("/room/clone", name="room_clone")
     */
    public
    function roomClone(RoomGeneratorService $roomGeneratorService, RoomCheckService $roomCheckService, Request $request, UserService $userService, TranslatorInterface $translator, SchedulingService $schedulingService, ServerUserManagment $serverUserManagment)
    {

        $roomOld = $this->doctrine->getRepository(Rooms::class)->find($request->get('room'));
        $room = clone $roomOld;
        $room->setStart(null);
        $room->setStartUtc(null);
        $room->setStartTimestamp(null);
        $room->setEnddate(null);
        $room->setEndDateUtc(null);
        $room->setEndTimestamp(null);
        $room = $roomGeneratorService->createCallerId($room);
        // here we clean all the scheduls from the old room
        foreach ($room->getSchedulings() as $data) {
            $room->removeScheduling($data);
        }
        $room->setUid(rand(01, 99) . time());
        $room->setSequence(0);

        $snack = $translator->trans('Keine Berechtigung');
        $title = $translator->trans('Konferenz duplizieren');

        if (UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $room)) {

            $servers = $serverUserManagment->getServersFromUser($this->getUser());
            $form = $this->createForm(RoomType::class, $room, ['server' => $servers, 'action' => $this->generateUrl('room_clone', ['room' => $room->getId()])]);
            $form->remove('scheduleMeeting');
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $room = $form->getData();
                foreach ($roomOld->getUserAttributes() as $data) {
                    $tmp = clone $data;
                    $room->addUserAttribute($tmp);
                }
                $room->setUidReal(md5(uniqid('h2-invent', true)));
                $room->setUidModerator(md5(uniqid()));
                $room->setUidParticipant(md5(uniqid()));
                $room->setSequence(0);
                $room->setUid(rand(0, 99) . time());
                $em = $this->doctrine->getManager();
                $error = array();
                $room = $roomCheckService->checkRoom($room, $error);
                if (sizeof($error) > 0) {
                    return new JsonResponse(array('error' => true, 'messages' => $error));
                }

                $em->persist($room);
                $em->flush();

                $schedulingService->createScheduling($room);
                foreach ($roomOld->getUser() as $user) {
                    $userService->addUser($user, $room);
                }
                $snack = $translator->trans('Konferenz erfolgreich erstellt');
                $this->addFlash('success', $snack);
                $this->addFlash('modalUrl', base64_encode($this->generateUrl('room_add_user', array('room' => $room->getId()))));
                $res = $this->generateUrl('dashboard');
                return new JsonResponse(array('error' => false, 'redirectUrl' => $res, 'cookie' => array('room_server' => $room->getServer()->getId())));

            }
            return $this->render('base/__newRoomModal.html.twig', array('form' => $form->createView(),'serverchoose'=>$serverChhose, 'title' => $title));
        }

        $snack = $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.');
        $this->addFlash('danger', $snack);
        $res = $this->generateUrl('dashboard');
        return new JsonResponse(array('error' => false, 'redirectUrl' => $res));
    }
}
