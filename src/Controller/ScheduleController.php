<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\Scheduling;
use App\Entity\SchedulingTime;
use App\Entity\SchedulingTimeUser;
use App\Entity\User;
use App\Form\Type\RoomType;
use App\Service\PexelService;
use App\Service\SchedulingService;
use App\Service\ServerUserManagment;
use App\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ScheduleController extends AbstractController
{
    /**
     * @Route("room/schedule/new", name="schedule_admin_new")
     */
    public function new( Request $request, TranslatorInterface $translator, ServerUserManagment $serverUserManagment, UserService $userService): Response
    {
        if ($request->get('id')) {
            $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('id' => $request->get('id')));
            if ($room->getModerator() !== $this->getUser()) {
                return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Keine Berechtigung')]);
            }
            $snack = $translator->trans('Terminplanung erfolgreich bearbeitet');
            $title = $translator->trans('Terminplanung bearbeiten');
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

            $snack = $translator->trans('Terminplanung erfolgreich erstellt');
            $title = $translator->trans('Neue Terminplanung erstellen');
        }
        $servers = $serverUserManagment->getServersFromUser($this->getUser());


        $form = $this->createForm(RoomType::class, $room, ['server' => $servers, 'action' => $this->generateUrl('schedule_admin_new', ['id' => $room->getId()])]);

        $form->remove('scheduleMeeting');
        $form->remove('start');
        $form->remove('scheduleMeeting');
       // try {
            $form->handleRequest($request);
        //} catch (\Exception $e) {
            $snack = $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.');
            //return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'danger'));
        //}
        if ($form->isSubmitted() && $form->isValid()) {

            $room = $form->getData();
            $room->setScheduleMeeting(true);
            $em = $this->getDoctrine()->getManager();
            $em->persist($room);
            $em->flush();
            if (sizeof($room->getSchedulings()->toArray()) < 1) {
                $schedule = new Scheduling();
                $schedule->setUid(md5(uniqid()));
                $schedule->setRoom($room);
                $em->persist($schedule);
                $em->flush();
                $room->addScheduling($schedule);
                $em->persist($room);
                $em->flush();
            }

            if ($request->get('id')) {
                foreach ($room->getUser() as $user) {
                    $userService->editRoom($user, $room);
                }
            } else {
                $userService->addUser($room->getModerator(), $room);
            }

            $modalUrl = base64_encode($this->generateUrl('schedule_admin', array('id' => $room->getId())));

            return $this->redirectToRoute('dashboard', ['snack' => $snack, 'modalUrl' => $modalUrl]);


        }
        return $this->render('base/__newRoomModal.html.twig', array('form' => $form->createView(), 'title' => $title));
    }

    /**
     * @Route("room/schedule/admin/{id}", name="schedule_admin",methods={"GET"})
     * @ParamConverter("room", options={"mapping"={"room"="id"}})
     */
    public function index(Rooms $rooms, Request $request): Response
    {
        if ($rooms->getModerator() !== $this->getUser()) {
            throw new NotFoundHttpException('Room not found');
        }
        $sheduls = $rooms->getSchedulings();

        return $this->render('schedule/index.html.twig', [
            'room' => $rooms,
        ]);
    }

    /**
     * @Route("room/schedule/admin/add/{id}", name="schedule_admin_add",methods={"POST"})
     * @ParamConverter("room", options={"mapping"={"room"="id"}})
     */
    public function add(Rooms $rooms, Request $request): Response
    {
        if ($rooms->getModerator() !== $this->getUser()) {
            throw new NotFoundHttpException('Room not found');
        }
        try {
            $schedule = $rooms->getSchedulings();
            if (sizeof($schedule) == 0) {
                $schedule = new  Scheduling();
                $schedule->setUid(md5(uniqid()));
                $schedule->setRoom($rooms);

            } else {
                $schedule = $schedule[0];
            }
            $em = $this->getDoctrine()->getManager();
            $scheduleTime = new SchedulingTime();
            $scheduleTime->setTime(new \DateTime($request->get('date')));
            $scheduleTime->setScheduling($schedule);
            $em->persist($schedule);
            $em->persist($scheduleTime);
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse(array('error' => true));
        }

        return new JsonResponse(array('error' => false));
    }

    /**
     * @Route("room/schedule/admin/remove/{id}", name="schedule_admin_remove",methods={"DELETE"})
     * @ParamConverter("schedulingTime")
     */
    public function remove(SchedulingTime $schedulingTime, Request $request): Response
    {
        if ($schedulingTime->getScheduling()->getRoom()->getModerator() !== $this->getUser()) {
            throw new NotFoundHttpException('Room not found');
        }
        try {

            $em = $this->getDoctrine()->getManager();
            foreach ($schedulingTime->getSchedulingTimeUsers() as $data) {
                $em->remove($data);
            }

            $em->remove($schedulingTime);
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse(array('error' => true));
        }

        return new JsonResponse(array('error' => false));
    }

    /**
     * @Route("room/schedule/admin/choose/{id}", name="schedule_admin_choose",methods={"GET"})
     * @ParamConverter("schedulingTime")
     */
    public function choose(SchedulingTime $schedulingTime, Request $request, SchedulingService $schedulingService, TranslatorInterface $translator): Response
    {
        if ($schedulingTime->getScheduling()->getRoom()->getModerator() !== $this->getUser()) {
            throw new NotFoundHttpException('Room not found');
        }
        $text = $translator->trans('Sie haben den Terminplan erfolgreich umgewandelt');
        if (!$schedulingService->chooseTimeSlot($schedulingTime)) {
            $text = $translator->trans('Fehler, Bitte Laden Sie die Seite neu');
        };
        return $this->redirectToRoute('dashboard', array('snack' => $text));
    }

    /**
     * @Route("schedule/{scheduleId}/{userId}", name="schedule_public_main", methods={"GET"})
     * @ParamConverter("user", class="App\Entity\User",options={"mapping": {"userId": "uid"}})
     * @ParamConverter("scheduling", class="App\Entity\Scheduling",options={"mapping": {"scheduleId": "uid"}})
     */
    public function public(Scheduling $scheduling, User $user, Request $request, PexelService $pexelService, TranslatorInterface $translator): Response
    {
        if (!in_array($user, $scheduling->getRoom()->getUser()->toArray())) {
            return $this->redirectToRoute('join_index_no_slug', ['snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.'), 'color' => 'danger']);

        }
        if (!$scheduling->getRoom()->getScheduleMeeting()) {
            return $this->redirectToRoute('join_index_no_slug', ['snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.'), 'color' => 'danger']);
        }

        $server = $scheduling->getRoom()->getServer();



        return $this->render('schedule/schedulePublic.html.twig', array('user' => $user, 'scheduling' => $scheduling, 'room' => $scheduling->getRoom(), 'server' => $server));
    }

    /**
     * @Route("schedule/vote", name="schedule_public_vote", methods={"POST"})
     */
    public function vote(Request $request, TranslatorInterface $translator): Response
    {
        $user = $this->getDoctrine()->getRepository(User::class)->find($request->get('user'));
        $scheduleTime = $this->getDoctrine()->getRepository(SchedulingTime::class)->find($request->get('time'));
        $type = $request->get('type');
        if (!in_array($user, $scheduleTime->getScheduling()->getRoom()->getUser()->toArray())) {
            return new JsonResponse(array('error' => true, 'text' => $translator->trans('Fehler'), 'color' => 'danger'));
        }
        $scheduleTimeUser = $this->getDoctrine()->getRepository(SchedulingTimeUser::class)->findOneBy(array('user' => $user, 'scheduleTime' => $scheduleTime));
        if (!$scheduleTimeUser) {
            $scheduleTimeUser = new SchedulingTimeUser();
            $scheduleTimeUser->setUser($user);
            $scheduleTimeUser->setScheduleTime($scheduleTime);
        }
        $scheduleTimeUser->setAccept($type);
        $em = $this->getDoctrine()->getManager();
        $em->persist($scheduleTimeUser);
        $em->flush();
        return new JsonResponse(array('error' => false, 'text' => $translator->trans('Erfolgreich bestätigt'), 'color' => 'success'));
    }
}
