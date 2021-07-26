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
use App\Service\UserService;
use App\Service\InviteService;

use App\Service\RoomService;
use App\UtilsHelper;
use phpDocumentor\Reflection\Types\This;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function newRoom(ServerService $serverService, SchedulingService $schedulingService, Request $request, UserService $userService, TranslatorInterface $translator, ServerUserManagment $serverUserManagment)
    {
        if ($request->get('id')) {
            $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('id' => $request->get('id')));
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
            $room->addUser($this->getUser());
            $room->setDuration(60);
            $room->setUid(rand(01, 99) . time());
            $room->setModerator($this->getUser());
            $room->setSequence(0);
            $room->setUidReal(md5(uniqid('h2-invent', true)));
            $room->setUidModerator(md5(uniqid('h2-invent', true)));
            $room->setUidParticipant(md5(uniqid('h2-invent', true)));

            $snack = $translator->trans('Konferenz erfolgreich erstellt');
            $title = $translator->trans('Neue Konferenz erstellen');
        }
        $servers = $serverUserManagment->getServersFromUser($this->getUser());


        $form = $this->createForm(RoomType::class, $room, ['server' => $servers, 'action' => $this->generateUrl('room_new', ['id' => $room->getId()])]);
        $form->remove('scheduleMeeting');

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                $room = $form->getData();
                if (!$room->getStart() && !$room->getPersistantRoom()) {
                    $snack = $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.');
                    return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'danger'));
                }
                $room = $this->setRoomProps($room, $serverService);

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
                if (!$room->getTotalOpenRooms()) {
                    return $this->redirectToRoute('dashboard', ['snack' => $snack, 'modalUrl' => $modalUrl]);
                } else {
                    return $this->redirectToRoute('dashboard', ['snack' => $snack]);
                }


            }
        } catch (\Exception $e) {
            $snack = $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.');
            return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'danger'));
        }
        return $this->render('base/__newRoomModal.html.twig', array('form' => $form->createView(), 'title' => $title));
    }

    /**
     * @Route("/room/add-user", name="room_add_user")
     */
    public function roomAddUser(Request $request, RoomAddService $roomAddService)
    {
        $newMember = array();
        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(['id' => $request->get('room')]);
        if ($room->getModerator() !== $this->getUser()) {
            return $this->redirectToRoute('dashboard', ['snack' => 'Keine Berechtigung']);
        }
        $form = $this->createForm(NewMemberType::class, $newMember, ['action' => $this->generateUrl('room_add_user', ['room' => $room->getId()])]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            $newMembers = $form->getData();
            $falseEmail = [];
            $falseEmail = array_merge(
                $roomAddService->createParticipants($newMembers['member'], $room),
                $roomAddService->createModerators($newMembers['moderator'], $room)
            );

            if (sizeof($falseEmail) > 0) {
                $emails = implode(", ", $falseEmail);
                $snack = $this->translator->trans("Einige Teilnehmer eingeladen. {emails} ist/sind nicht korrekt und können nicht eingeladen werden", array('{emails}' => $emails));
            } else {
                $snack = $this->translator->trans('Teilnehmer wurden eingeladen');
            }

            return $this->redirectToRoute('dashboard', ['snack' => $snack]);
        }

        $title = $this->translator->trans('Teilnehmer verwalten');

        return $this->render('room/attendeeModal.twig', array('form' => $form->createView(), 'title' => $title, 'room' => $room));
    }

    /**
     * @Route("/room/join/{t}/{room}", name="room_join")
     * @ParamConverter("room", options={"mapping"={"room"="id"}})
     */
    public
    function joinRoom(RoomService $roomService, Rooms $room, $t)
    {

        if (in_array($this->getUser(), $room->getUser()->toarray())) {
            $url = $roomService->join($room, $this->getUser(), $t, $this->getUser()->getFirstName() . ' ' . $this->getUser()->getLastName());
            if ($this->getUser() == $room->getModerator() && $room->getTotalOpenRooms() && $room->getPersistantRoom()) {
                $room->setStart(new \DateTime());
                if ($room->getTotalOpenRoomsOpenTime()) {
                    $room->setEnddate((new \DateTime())->modify('+ ' . $room->getTotalOpenRoomsOpenTime() . ' min'));
                }
                $em = $this->getDoctrine()->getManager();
                $em->persist($room);
                $em->flush();
            }
            $now = new \DateTime();
            if (($room->getStart() === null || $room->getStart()->modify('-30min') < $now && $room->getEnddate() > $now) || $this->getUser() == $room->getModerator()) {
                return $this->redirect($url);
            }
            return $this->redirectToRoute('dashboard', ['color' => 'danger', 'snack' => $this->translator->trans('Der Beitritt ist nur von {from} bis {to} möglich',
                    array(
                        '{from}' => $room->getStart()->format('d.m.Y H:i'),
                        '{to}' => $room->getEnddate()->format('d.m.Y H:i')
                    ))
                ]
            );
        }

        return $this->redirectToRoute('dashboard', [
            'color' => 'danger',
                'snack' => $this->translator->trans('Konferenz nicht gefunden. Zugangsdaten erneut eingeben')
            ]
        );
    }

    /**
     * @Route("/room/user/remove", name="room_user_remove")
     */
    public
    function roomUserRemove(Request $request, UserService $userService, RoomAddService $roomAddService)
    {

        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(['id' => $request->get('room')]);
        $repeater = false;

        if ($room->getRepeater()) {
            $repeater = true;
        }
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['id' => $request->get('user')]);
        $snack = 'Keine Berechtigung';
        if ($room->getModerator() === $this->getUser() || $user === $this->getUser()) {
            if (!$repeater) {
                $room->removeUser($user);
                $em = $this->getDoctrine()->getManager();
                $em->persist($room);
                $em->flush();
                $userService->removeRoom($user, $room);
            } else {
                $roomAddService->removeUserFromRoom($user, $room);
            }

            $snack = $this->translator->trans('Teilnehmer gelöscht');
        }

        return $this->redirectToRoute('dashboard', ['snack' => $snack]);
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
            $room->setModerator(null);
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
