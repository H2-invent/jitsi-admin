<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\Scheduling;
use App\Entity\SchedulingTime;
use App\Entity\SchedulingTimeUser;
use App\Entity\Server;
use App\Entity\User;
use App\Form\Type\RoomType;
use App\Helper\JitsiAdminController;
use App\Service\PexelService;
use App\Service\RoomGeneratorService;
use App\Service\SchedulingService;
use App\Service\ServerUserManagment;
use App\Service\ThemeService;
use App\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ScheduleController extends JitsiAdminController
{
    /**
     * @Route("room/schedule/new", name="schedule_admin_new")
     */
    public function new(RoomGeneratorService $roomGeneratorService, ParameterBagInterface $parameterBag, Request $request, TranslatorInterface $translator, ServerUserManagment $serverUserManagment, UserService $userService, SchedulingService $schedulingService): Response
    {
        $servers = $serverUserManagment->getServersFromUser($this->getUser());
        if ($request->get('id')) {
            $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(array('id' => $request->get('id')));
            if ($room->getModerator() !== $this->getUser()) {
                $this->addFlash('danger', $translator->trans('Keine Berechtigung'));
                return $this->redirectToRoute('dashboard');
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
            $room = $roomGeneratorService->createRoom($this->getUser(),$serverChhose);
            $snack = $translator->trans('Terminplanung erfolgreich erstellt');
            $title = $translator->trans('Neue Terminplanung erstellen');
        }
        $servers = $serverUserManagment->getServersFromUser($this->getUser());


        $form = $this->createForm(RoomType::class, $room, ['server' => $servers, 'action' => $this->generateUrl('schedule_admin_new', ['id' => $room->getId()]),'isEdit'=>(bool)$request->get('id')]);

        $form->remove('scheduleMeeting');
        $form->remove('start');
        $form->remove('persistantRoom');
        $form->remove('totalOpenRooms');
        $form->remove('totalOpenRoomsOpenTime');
        try {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                $room = $form->getData();
                $error = array();

                if (!$room->getName()) {
                    $error[] = $translator->trans('Fehler, der Name darf nicht leer sein');
                }

                if (sizeof($error) > 0) {
                    return new JsonResponse(array('error' => true, 'messages' => $error));
                }

                $room->setScheduleMeeting(true);
                $em = $this->doctrine->getManager();
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

                $modalUrl = base64_encode($this->generateUrl('schedule_admin', array('id' => $room->getId())));
                $res = $this->generateUrl('dashboard');
                $this->addFlash('success', $snack);
                $this->addFlash('modalUrl', $modalUrl);
                return new JsonResponse(array('error'=>false, 'redirectUrl'=>$res,'cookie'=>array('room_server'=>$room->getServer()->getId())));

            }
        } catch (\Exception $e) {
            $snack = $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.');
            $this->addFlash('danger',$snack );
            $res = $this->generateUrl('dashboard');
            return new JsonResponse(array('error'=>false,'redirectUrl'=>$res));
        }
        return $this->render('base/__newRoomModal.html.twig', array('server'=>$servers, 'form' => $form->createView(), 'title' => $title));
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
            $em = $this->doctrine->getManager();
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

            $em = $this->doctrine->getManager();
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
        $color = 'success';
        if (!$schedulingService->chooseTimeSlot($schedulingTime)) {
            $text = $translator->trans('Fehler, Bitte Laden Sie die Seite neu');
            $color='danger';
        };
        $this->addFlash($color,$text);
        return $this->redirectToRoute('dashboard');
    }

    /**
     * @Route("schedule/{scheduleId}/{userId}", name="schedule_public_main", methods={"GET"})
     * @ParamConverter("user", class="App\Entity\User",options={"mapping": {"userId": "uid"}})
     * @ParamConverter("scheduling", class="App\Entity\Scheduling",options={"mapping": {"scheduleId": "uid"}})
     */
    public function public(Scheduling $scheduling, User $user, Request $request, PexelService $pexelService, TranslatorInterface $translator): Response
    {
        if (!in_array($user, $scheduling->getRoom()->getUser()->toArray())) {
            $this->addFlash('danger', $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.'));
            return $this->redirectToRoute('join_index_no_slug');

        }
        if (!$scheduling->getRoom()->getScheduleMeeting()) {
            $this->addFlash('danger', $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.'));
            return $this->redirectToRoute('join_index_no_slug');
        }

        $server = $scheduling->getRoom()->getServer();
        return $this->render('schedule/schedulePublic.html.twig', array('user' => $user, 'scheduling' => $scheduling, 'room' => $scheduling->getRoom(), 'server' => $server));
    }

    /**
     * @Route("schedule/vote", name="schedule_public_vote", methods={"POST"})
     */
    public function vote(Request $request, TranslatorInterface $translator): Response
    {
        $user = $this->doctrine->getRepository(User::class)->find($request->get('user'));
        $scheduleTime = $this->doctrine->getRepository(SchedulingTime::class)->find($request->get('time'));
        $type = $request->get('type');
        if (!in_array($user, $scheduleTime->getScheduling()->getRoom()->getUser()->toArray())) {
            return new JsonResponse(array('error' => true, 'text' => $translator->trans('Fehler'), 'color' => 'danger'));
        }
        $scheduleTimeUser = $this->doctrine->getRepository(SchedulingTimeUser::class)->findOneBy(array('user' => $user, 'scheduleTime' => $scheduleTime));
        if (!$scheduleTimeUser) {
            $scheduleTimeUser = new SchedulingTimeUser();
            $scheduleTimeUser->setUser($user);
            $scheduleTimeUser->setScheduleTime($scheduleTime);
        }
        $scheduleTimeUser->setAccept($type);
        $em = $this->doctrine->getManager();
        $em->persist($scheduleTimeUser);
        $em->flush();
        return new JsonResponse(array('error' => false, 'text' => $translator->trans('common.success.save'), 'color' => 'success'));
    }
}
