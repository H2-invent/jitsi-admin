<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Entity\Scheduling;
use App\Entity\Server;
use App\Entity\User;
use App\Form\Type\NewMemberType;
use App\Form\Type\RoomType;
use App\Service\PermissionChangeService;
use App\Service\RepeaterService;
use App\Service\RoomAddService;
use App\Service\SchedulingService;
use App\Service\ServerService;
use App\Service\ServerUserManagment;
use App\Service\ThemeService;
use App\Service\UserService;
use App\Service\InviteService;

use App\Service\RoomService;
use App\UtilsHelper;
use phpDocumentor\Reflection\Types\This;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class RoomController extends AbstractController
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @Route("/room/new", name="room_new")
     */
    public function newRoom(ParameterBagInterface $parameterBag, ThemeService $themeService, ServerService $serverService, SchedulingService $schedulingService, Request $request, UserService $userService, TranslatorInterface $translator, ServerUserManagment $serverUserManagment)
    {
        $servers = $serverUserManagment->getServersFromUser($this->getUser());
        if ($request->get('id')) {
            $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('id' => $request->get('id')));
            if (!$room) {
                return $this->redirectToRoute('dashboard', array('snack' => $translator->trans('Fehler'), 'color' => 'danger'));
            }
            if ($room->getModerator() !== $this->getUser()) {
                return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Keine Berechtigung')]);
            }
            $snack = $translator->trans('Konferenz erfolgreich bearbeitet');
            $title = $translator->trans('Konferenz bearbeiten');
            $sequence = $room->getSequence() + 1;
            $room->setSequence($sequence);
            if (!$room->getUidModerator()) {
                $room->setUidModerator(md5(uniqid('h2-invent', true)));
            }
            if (!$room->getUidParticipant()) {
                $room->setUidParticipant(md5(uniqid('h2-invent', true)));
            }

        } else {
            $room = new Rooms();
            if ($request->cookies->has('room_server')) {

                $server = $this->getDoctrine()->getRepository(Server::class)->find($request->cookies->get('room_server'));

                if ($server && in_array($server, $servers)) {
                    $room->setServer($server);
                }
            }
            $room->addUser($this->getUser());
            $room->setDuration(60);
            $room->setUid(rand(01, 99) . time());
            $room->setModerator($this->getUser());
            $room->setSequence(0);
            $room->setUidReal(md5(uniqid('h2-invent', true)));
            $room->setUidModerator(md5(uniqid('h2-invent', true)));
            $room->setUidParticipant(md5(uniqid('h2-invent', true)));
            // here we set the default values
            $room->setPersistantRoom($parameterBag->get('input_settings_persistant_rooms_default'));
            $room->setOnlyRegisteredUsers($parameterBag->get('input_settings_only_registered_default'));
            $room->setPublic($parameterBag->get('input_settings_share_link_default'));
            if ($parameterBag->get('input_settings_max_participants_default') > 0) {
                $room->setMaxParticipants($parameterBag->get('input_settings_max_participants_default'));
            }
            $room->setWaitinglist($parameterBag->get('input_settings_waitinglist_default'));
            $room->setShowRoomOnJoinpage($parameterBag->get('input_settings_conference_join_page_default'));
            $room->setTotalOpenRooms($parameterBag->get('input_settings_deactivate_participantsList_default'));
            $room->setDissallowScreenshareGlobal($parameterBag->get('input_settings_dissallow_screenshare_default'));
            $room->setLobby($parameterBag->get('input_settings_allowLobby_default'));

            //end default values

            if ($this->getUser()->getTimeZone() && $parameterBag->get('allowTimeZoneSwitch') == 1) {
                $room->setTimeZone($this->getUser()->getTimeZone());
                if ($parameterBag->get('input_settings_allow_timezone_default') != 0) {
                    $room->setTimeZone($parameterBag->get('input_settings_allow_timezone_default'));
                }
            }
            $snack = $translator->trans('Konferenz erfolgreich erstellt');
            $title = $translator->trans('Neue Konferenz erstellen');
        }


        $form = $this->createForm(RoomType::class, $room, ['server' => $servers, 'action' => $this->generateUrl('room_new', ['id' => $room->getId()])]);
        $form->remove('scheduleMeeting');

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                $room = $form->getData();

                $now = new \DateTime();
                $error = array();
                if (!$room->getStart() && !$room->getPersistantRoom()) {
                    $error[] = $translator->trans('Fehler, das Startdatum darf nicht leer sein');
                }
                if (!$room->getName()) {
                    $error[] = $translator->trans('Fehler, der Name darf nicht leer sein');
                }
                if($room->getStart()){
                    $room = $this->setRoomProps($room, $serverService);
                    if (($room->getStart() < $now && $room->getEnddate() < $now) && !$room->getPersistantRoom()) {
                        $error[] = $this->translator->trans('Fehler, das Startdatum und das Enddatum liegen in der Vergangenheit');
                    }
                }

                if (sizeof($error) > 0) {
                    return new JsonResponse(array('error' => true, 'messages' => $error));
                }

                $em = $this->getDoctrine()->getManager();
                $em->persist($room);
                $em->flush();
                $schedulingService->createScheduling($room);

                if ($request->get('id')) {
                    foreach ($room->getUser() as $user) {
                        $userService->editRoom($user, $room);
                    }
                } else {
                    $userService->addUser($room->getModerator(), $room);
                }

                $modalUrl = base64_encode($this->generateUrl('room_add_user', array('room' => $room->getId())));
                if ($room->getScheduleMeeting()) {
                    $modalUrl = base64_encode($this->generateUrl('schedule_admin', array('id' => $room->getId())));
                }
                $res = $this->generateUrl('dashboard', ['snack' => $snack, 'modalUrl' => $modalUrl]);

                return new JsonResponse(array('error'=>false, 'redirectUrl'=>$res,'cookie'=>array('room_server'=>$room->getServer()->getId())));

            }
        } catch (\Exception $e) {
            $snack = $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.');
            $res = $this->generateUrl('dashboard', array('snack' => $snack, 'color' => 'danger'));

            return new JsonResponse(array('error'=>false,'redirectUrl'=>$res));
        }
        return $this->render('base/__newRoomModal.html.twig', array('form' => $form->createView(), 'title' => $title));
    }


    /**
     * @Route("/room/remove", name="room_remove")
     */
    public
    function roomRemove(Request $request, UserService $userService, RepeaterService $repeaterService, TranslatorInterface $translator)
    {

        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(['id' => $request->get('room')]);
        $snack = 'Keine Berechtigung';
        if ($this->getUser() === $room->getModerator()) {
            $em = $this->getDoctrine()->getManager();
            foreach ($room->getUser() as $user) {
                if (!$room->getRepeater()) {
                    $userService->removeRoom($user, $room);
                }
                $room->removeUser($user);
                $em->persist($room);
            }
            if ($room->getRepeater()) {
                $repeater = $room->getRepeater();
                $repeaterService->sendEMail($repeater, 'email/repeaterEdit.html.twig', $translator->trans('Die Serienvideokonferenz {name} wurde bearbeitet', array('{name}' => $repeater->getPrototyp()->getName())), array('room' => $repeater->getPrototyp()));
                $room->setRepeater(null);
            }
            foreach ($room->getFavoriteUsers() as $data){
                $room->removeFavoriteUser($data);
            }
            $room->setModerator(null);
            foreach ($room->getFavoriteUsers() as $data){
                $data->removeFavorite($room);
                $em->persist($data);
            }
            $em->persist($room);
            $em->flush();
            $snack = $this->translator->trans('Konferenz gelöscht');
        }
        return $this->redirectToRoute('dashboard', ['snack' => $snack]);
    }

    /**
     * @Route("/room/clone", name="room_clone")
     */
    public
    function roomClone(Request $request, ServerService $serverService, UserService $userService, TranslatorInterface $translator, SchedulingService $schedulingService, ServerUserManagment $serverUserManagment)
    {

        $roomOld = $this->getDoctrine()->getRepository(Rooms::class)->find($request->get('room'));
        $room = clone $roomOld;
        // here we clean all the scheduls from the old room
        foreach ($room->getSchedulings() as $data) {
            $room->removeScheduling($data);
        }
        $room->setUid(rand(01, 99) . time());
        $room->setSequence(0);

        $snack = $translator->trans('Keine Berechtigung');
        $title = $translator->trans('Konferenz duplizieren');

        if ($this->getUser() === $room->getModerator()) {

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
                $this->setRoomProps($room, $serverService);
                $em = $this->getDoctrine()->getManager();
                $em->persist($room);
                $em->flush();

                $schedulingService->createScheduling($room);
                foreach ($roomOld->getUser() as $user) {

                    $userService->addUser($user, $room);
                }
                $snack = $translator->trans('Teilnehmer bearbeitet');
                return $this->redirectToRoute('dashboard', ['snack' => $snack, 'modalUrl' => base64_encode($this->generateUrl('room_add_user', array('room' => $room->getId())))]);
            }
            return $this->render('base/__newRoomModal.html.twig', array('form' => $form->createView(), 'title' => $title));
        }

        return $this->redirectToRoute('dashboard', ['snack' => $snack]);
    }

    function setRoomProps(Rooms $room, ServerService $serverService)
    {
        if ($room->getPersistantRoom()) {
            $counter = 0;
            $slug = UtilsHelper::slugify($room->getName());
            $tmp = $slug . '-' . rand(10, 1000);
            if (!$room->getSlug()) {
                while (true) {
                    $roomTmp = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(['uid' => $tmp]);
                    if (!$roomTmp) {
                        $room->setUid($tmp);
                        $room->setSlug($tmp);
                        break;
                    } else {
                        $counter++;
                        $tmp = $slug . '-' . rand(10, 1000);
                    }
                }
            }
            $room->setStart(null);
            $room->setEnddate(null);

        } else {
            $room->setEnddate((clone $room->getStart())->modify('+ ' . $room->getDuration() . ' minutes'));
        }
        return $room;
    }
}
