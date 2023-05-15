<?php

namespace App\Controller;

use App\Entity\Scheduling;
use App\Entity\SchedulingTime;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\SchedulingService;
use App\UtilsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/scheduler/public/creator', name: 'app_scheduler_public_creator')]
class SchedulerPublicCreatorController extends AbstractController
{
    public function __construct(
        private RoomsRepository        $roomsRepository,
        private UserRepository         $userRepo,
        private EntityManagerInterface $entityManager,
        private SchedulingService      $schedulingService,
    )
    {
    }

    #[Route('/', name: '')]
    public function index(Request $request): Response
    {
        $room = $this->roomsRepository->findOneBy(['uid' => $request->get('room_id')]);
        $user = $this->userRepo->findOneBy(['uid' => $request->get('user_id')]);
        if (!$room) {
            throw new NotFoundHttpException('Scheduler not found');
        }
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }
        return $this->render(
            'scheduler_public_creator/index.html.twig',
            [
                'user' => $user,
                'room' => $room,
            ]
        );

    }

    #[Route('/add', name: '_add')]
    public function add(Request $request): Response
    {
        $room = $this->roomsRepository->findOneBy(['uid' => $request->get('room_id')]);
        $user = $this->userRepo->findOneBy(['uid' => $request->get('user_id')]);
        if (!$room) {
            throw new NotFoundHttpException('Scheduler not found');
        }
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }
        try {
            $schedule = $room->getSchedulings();
            if (count($schedule) > 0) {
                $schedule = $schedule[0];
            } else {
                throw new NotFoundHttpException('This Room is no scheduler');
            }

            $scheduleTime = new SchedulingTime();
            $scheduleTime->setTime(new \DateTime($request->get('date')));
            $scheduleTime->setScheduling($schedule);
            $scheduleTime->setCreatedFrom($user);
            $schedule->addSchedulingTime($scheduleTime);
            $schedule->setCompletedEmailSent(false);
            $this->entityManager->persist($schedule);
            $this->entityManager->persist($scheduleTime);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['error' => true]);
        }
        $this->schedulingService->sendEmailWhenNewSchedulingTime(schedulingTime: $scheduleTime);
        return new JsonResponse(['error' => false]);

    }

}
