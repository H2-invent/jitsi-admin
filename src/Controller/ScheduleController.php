<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\Scheduling;
use App\Entity\SchedulingTime;
use App\Entity\SchedulingTimeUser;
use App\Entity\User;
use App\Service\PexelService;
use App\Service\SchedulingService;
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
            foreach ($schedulingTime->getSchedulingTimeUsers() as $data){
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
    public function choose(SchedulingTime $schedulingTime, Request $request,SchedulingService $schedulingService,TranslatorInterface $translator): Response
    {
        if ($schedulingTime->getScheduling()->getRoom()->getModerator() !== $this->getUser()) {
            throw new NotFoundHttpException('Room not found');
        }
        $text = $translator->trans('Sie haben den Terminplan erfolgreich umgewandelt');
        if(!$schedulingService->chooseTimeSlot($schedulingTime)){
         $text = $translator->trans('Fehler, Bitte Laden Sie die Seite neu');
        };
        return $this->redirectToRoute('dashboard',array('snack'=>$text));
    }
    /**
     * @Route("schedule/{scheduleId}/{userId}", name="schedule_public_main", methods={"GET"})
     * @ParamConverter("user", class="App\Entity\User",options={"mapping": {"userId": "uid"}})
     * @ParamConverter("scheduling", class="App\Entity\Scheduling",options={"mapping": {"scheduleId": "uid"}})
     */
    public function public(Scheduling $scheduling, User $user,Request $request,PexelService $pexelService,TranslatorInterface $translator): Response
    {
        if (!in_array($user,$scheduling->getRoom()->getUser()->toArray())) {
            return $this->redirectToRoute('join_index_no_slug', ['snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.'), 'color'=>'danger']);

        }
        if (!$scheduling->getRoom()->getScheduleMeeting()) {
            return $this->redirectToRoute('join_index_no_slug', ['snack' => $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.'), 'color'=>'danger']);
        }

        $server = $scheduling->getRoom()->getServer();
        $image = $pexelService->getImageFromPexels();


        return $this->render('schedule/schedulePublic.html.twig',array('user'=>$user,'scheduling'=>$scheduling,'room'=>$scheduling->getRoom(),'server'=>$server,'image'=>$image));
    }
    /**
     * @Route("schedule/vote", name="schedule_public_vote", methods={"POST"})
     */
    public function vote(Request $request,TranslatorInterface $translator): Response
    {
        $user = $this->getDoctrine()->getRepository(User::class)->find($request->get('user'));
        $scheduleTime = $this->getDoctrine()->getRepository(SchedulingTime::class)->find($request->get('time'));
        $type=$request->get('type');
        if (!in_array($user,$scheduleTime->getScheduling()->getRoom()->getUser()->toArray())) {
            return new JsonResponse(array('error'=>true,'text'=>$translator->trans('Fehler'),'color'=>'danger'));
        }
        $scheduleTimeUser = $this->getDoctrine()->getRepository(SchedulingTimeUser::class)->findOneBy(array('user'=>$user,'scheduleTime'=>$scheduleTime));
        if(!$scheduleTimeUser){
            $scheduleTimeUser = new SchedulingTimeUser();
            $scheduleTimeUser->setUser($user);
            $scheduleTimeUser->setScheduleTime($scheduleTime);
        }
        $scheduleTimeUser->setAccept($type);
        $em = $this->getDoctrine()->getManager();
        $em->persist($scheduleTimeUser);
        $em->flush();
        return new JsonResponse(array('error'=>false,'text'=>$translator->trans('Erfolgreich bestätigt'),'color'=>'success'));
    }
}
