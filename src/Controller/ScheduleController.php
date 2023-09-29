<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\Scheduling;
use App\Entity\SchedulingTime;
use App\Entity\SchedulingTimeUser;
use App\Entity\User;
use App\Form\Type\SchedulerType;
use App\Helper\JitsiAdminController;
use App\Repository\RoomsRepository;
use App\Repository\SchedulingTimeRepository;
use App\Repository\SchedulingTimeUserRepository;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use App\Service\NewRoomService;
use App\Service\PexelService;
use App\Service\RoomGeneratorService;
use App\Service\SchedulingService;
use App\Service\ServerUserManagment;
use App\Service\UserService;
use App\Util\CsvHandler;
use App\UtilsHelper;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;

use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ScheduleController extends JitsiAdminController
{
    public function __construct(
        ManagerRegistry           $managerRegistry,
        TranslatorInterface       $translator,
        LoggerInterface           $logger,
        ParameterBagInterface     $parameterBag,
        private SchedulingService $schedulingService)
    {
        parent::__construct(
            $managerRegistry,
            $translator,
            $logger,
            $parameterBag,);
    }

    /**
     * @Route("room/schedule/new", name="schedule_admin_new")
     */
    public function new(
        RoomGeneratorService $roomGeneratorService,
        Request              $request,
        TranslatorInterface  $translator,
        ServerUserManagment  $serverUserManagement,
        UserService          $userService,
        SchedulingService    $schedulingService,
        RoomsRepository      $roomsRepository,
        ServerRepository     $serverRepository,
        NewRoomService       $newRoomService,
        FormFactoryInterface $formFactory,
    ): Response
    {
        $room = $newRoomService->newRoomService(request: $request, myUser: $this->getUser());
        if ($room instanceof Response) {
            return $room;
        }
        $servers = $serverUserManagement->getServersFromUser($this->getUser());

        $id = $request->get('id') ?? null;
        $edit = ($id !== null);
        $snack = $translator->trans('Terminplanung erfolgreich erstellt');
        $title = $edit ? $translator->trans('Terminplanung bearbeiten') : $translator->trans('Neue Terminplanung erstellen');

        $roomOld = clone $room;

        $form = $this->createForm(
            SchedulerType::class,
            $room,
            [
                'block_name' => 'room',
                'user' => $this->getUser(),
                'server' => $servers,
                'action' => $this->generateUrl(
                    'schedule_admin_new',
                    [
                        'id' => $room->getId()
                    ]
                ),
                'isEdit' => $edit,
            ]
        );

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
                $error = [];

                if (!$room->getName()) {
                    $error[] = $translator->trans('Fehler, der Name darf nicht leer sein');
                }

                if (count($error) > 0) {
                    return new JsonResponse(
                        [
                            'error' => true,
                            'messages' => $error
                        ]
                    );
                }

                $room->setScheduleMeeting(true);
                $em = $this->doctrine->getManager();
                $em->persist($room);
                $em->flush();
                $schedulingService->createScheduling($room);

                if ($edit) {
                    if ($newRoomService->roomChanged($roomOld, $room)) {
                        foreach ($room->getUser() as $user) {
                            $userService->editRoom($user, $room);
                        }
                    }
                    $snack = $translator->trans('Terminplanung erfolgreich bearbeitet');
                } else {
                    $roomGeneratorService->addUserToRoom($room->getModerator(), $room, true);
                    $userService->addUser($room->getModerator(), $room);
                }

                $modalUrl = base64_encode(
                    $this->generateUrl(
                        'schedule_admin',
                        [
                            'id' => $room->getId(),
                        ],
                    )
                );
                $res = $this->generateUrl('dashboard');
                $this->addFlash('success', $snack);
                $this->addFlash('modalUrl', $modalUrl);

                return new JsonResponse(
                    [
                        'error' => false,
                        'redirectUrl' => $res,
                        'cookie' => [
                            'room_server' => $room->getServer()->getId()
                        ],
                    ],
                );
            }
        } catch (Exception) {
            $snack = $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.');
            $this->addFlash('danger', $snack);
            $res = $this->generateUrl('dashboard');

            return new JsonResponse(['error' => false, 'redirectUrl' => $res]);
        }
        return $this->render(
            'base/__newRoomModal.html.twig',
            [
                'isEdit' => $edit,
                'server' => $servers,
                'serverchoose' => $room->getServer(),
                'form' => $form->createView(),
                'title' => $title,
            ]
        );
    }

    /**
     * @Route("room/schedule/admin/participants/{id}", name="schedule_admin_participants",methods={"GET"})
     */
    public function participants(
        Rooms   $rooms,
        Request $request
    ): Response
    {
        if (!UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $rooms)) {
            throw new NotFoundHttpException('Room not found');
        }
        $modalUrl = base64_encode($this->generateUrl('room_add_user', ['room' => $rooms->getId()]));
        $res = $this->redirectToRoute('dashboard');
        $this->addFlash('modalUrl', $modalUrl);
        return $res;
    }

    /**
     * @Route("room/schedule/admin/{id}", name="schedule_admin",methods={"GET"})
     */
    public function index(
        Rooms   $rooms,
        Request $request,
    ): Response
    {
        if (!UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $rooms)) {
            throw new NotFoundHttpException('Room not found');
        }
        return $this->render(
            'schedule/index.html.twig',
            [
                'room' => $rooms,
            ]
        );
    }

    /**
     * @Route("room/schedule/admin/add/{id}", name="schedule_admin_add",methods={"POST"})
     */
    public function add(
        Rooms   $rooms,
        Request $request
    ): Response
    {
        if (!UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $rooms)) {
            throw new NotFoundHttpException('Room not found');
        }
        try {
            $schedule = $rooms->getSchedulings();
            if (count($schedule) == 0) {
                $schedule = new  Scheduling();
                $schedule->setUid(md5(uniqid()));
                $schedule->setRoom($rooms);
            } else {
                $schedule = $schedule[0];
            }
            $em = $this->doctrine->getManager();
            $scheduleTime = new SchedulingTime();
            $scheduleTime->setTime(new DateTime($request->get('date')));
            $scheduleTime->setScheduling($schedule);
            $schedule->setCompletedEmailSent(false);
            $em->persist($schedule);
            $em->persist($scheduleTime);
            $em->flush();
        } catch (Exception $e) {
            return new JsonResponse(['error' => true]);
        }
        $this->schedulingService->sendEmailWhenNewSchedulingTime(schedulingTime: $scheduleTime);
        return new JsonResponse(['error' => false]);
    }

    /**
     * @Route("room/schedule/admin/remove/{id}", name="schedule_admin_remove",methods={"DELETE"})
     */
    public function remove(
        SchedulingTime $schedulingTime,
        Request        $request): Response
    {
        if (!UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $schedulingTime->getScheduling()->getRoom())) {
            throw new NotFoundHttpException('Room not found');
        }
        try {
            $em = $this->doctrine->getManager();
            foreach ($schedulingTime->getSchedulingTimeUsers() as $data) {
                $em->remove($data);
            }

            $em->remove($schedulingTime);
            $em->flush();
        } catch (Exception $e) {
            return new JsonResponse(['error' => true]);
        }

        return new JsonResponse(['error' => false]);
    }

    /**
     * @Route("room/schedule/admin/choose/{id}", name="schedule_admin_choose",methods={"GET"})
     */
    public function choose(
        SchedulingTime      $schedulingTime,
        Request             $request,
        SchedulingService   $schedulingService,
        TranslatorInterface $translator
    ): Response
    {
        if (!UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $schedulingTime->getScheduling()->getRoom())) {
            throw new NotFoundHttpException('Room not found');
        }
        $text = $translator->trans('Sie haben den Terminplan erfolgreich umgewandelt');
        $color = 'success';
        if (!$schedulingService->chooseTimeSlot($schedulingTime)) {
            $text = $translator->trans('Fehler, Bitte Laden Sie die Seite neu');
            $color = 'danger';
        }
        $this->addFlash($color, $text);
        return $this->redirectToRoute('dashboard');
    }

    /**
     * @Route("schedule/{scheduleId}/{userId}", name="schedule_public_main", methods={"GET"})
     */
    public function public(
        #[MapEntity(mapping: ['scheduleId' => 'uid'])]
        Scheduling          $scheduling,
        #[MapEntity(mapping: ['userId' => 'uid'])]
        User                $user,
        TranslatorInterface $translator,
    ): Response
    {
        if (!in_array($user, $scheduling->getRoom()->getUser()->toArray())
            || !$scheduling->getRoom()->getScheduleMeeting()
        ) {
            $this->addFlash('danger', $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.'));

            return $this->redirectToRoute('join_index_no_slug');
        }

        $server = $scheduling->getRoom()->getServer();
        return $this->render(
            'schedule/schedulePublic.html.twig',
            [
                'user' => $user,
                'scheduling' => $scheduling,
                'room' => $scheduling->getRoom(),
                'server' => $server,
            ],
        );
    }

    /**
     * @Route("schedule/vote", name="schedule_public_vote", methods={"POST"})
     */
    public function vote(
        Request                      $request,
        TranslatorInterface          $translator,
        UserRepository               $userRepository,
        SchedulingTimeRepository     $schedulingTimeRepository,
        SchedulingTimeUserRepository $schedulingTimeUserRepository,
        EntityManagerInterface       $em,
    ): Response
    {
        $user = $userRepository->find($request->get('user'));
        $scheduleTime = $schedulingTimeRepository->find($request->get('time'));
        $room = $scheduleTime->getScheduling()->getRoom();
        $type = $request->get('type');

        if (
            !in_array($user, $room->getUser()->toArray())
            || !$this->validateVote($type, $room->getAllowMaybeOption())
        ) {
            return new JsonResponse(
                [
                    'error' => true,
                    'text' => $translator->trans('Fehler'),
                    'color' => 'danger'
                ],
            );
        }
        $this->schedulingService->voteForSchedulingTime(user: $user, schedulingTime: $scheduleTime, type: $type);


        return new JsonResponse(
            [
                'error' => false,
                'text' => $translator->trans('common.success.save'),
                'color' => 'success',
            ],
        );
    }


    private function validateVote(int $vote, bool $allowMaybe): bool
    {
        return !(!$allowMaybe && $vote === 2);
    }

    #[Route(path: 'schedule/download/csv/{id}', name: 'schedule_download_csv', methods: ['GET'])]
    public function generateVoteCsv(
        Rooms $room
    ): Response
    {
        $votingsAndTimes = $this->getUserVotes($room);

        if (!isset($votingsAndTimes['times']) || count($votingsAndTimes['times']) === 0) {
            $this->addFlash('danger', $this->translator->trans('error.scheduler.noSchedules'));

            return $this->redirectToRoute('dashboard');
        }

        if (!isset($votingsAndTimes['user']) || count($votingsAndTimes['user']) === 0) {
            $this->addFlash('danger', $this->translator->trans('error.scheduler.novotings'));

            return $this->redirectToRoute('dashboard');
        }

        $votings = $this->fillAllVotings($votingsAndTimes['user'], array_unique($votingsAndTimes['times']));


        $csv = implode(PHP_EOL, CsvHandler::generateFromArray($votings, ';'));
        $response = new Response($csv);

        $response->headers->set(
            'Content-Disposition',
            HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                preg_replace('/[[:^print:]]/', '', $room->getName()) . '-' . (new DateTime())->format('d-m-Y_H-i') . '.csv',
            )
        );

        return $response;
    }

    private function getVoteString(int $vote): ?string
    {
        return match ($vote) {
            0 => $this->translator->trans(id: 'Ja', domain: 'messages'),
            1 => $this->translator->trans(id: 'Nein', domain: 'messages'),
            2 => $this->translator->trans(id: 'Vielleicht', domain: 'messages'),
            default => null,
        };
    }

    private function getUserVotes(Rooms $room): array
    {
        $votings = [];

        foreach ($room->getSchedulings() as $scheduling) {
            foreach ($scheduling->getSchedulingTimes() as $schedulingTime) {
                $schedulingTimeString = $schedulingTime->getTime()->format('d-m-Y H:i:s');
                $votings['times'][] = $schedulingTimeString;

                foreach ($schedulingTime->getSchedulingTimeUsers() as $schedulingTimeUser) {
                    $user = $schedulingTimeUser->getUser();
                    $name = implode(' ', [$user->getFirstName(), $user->getLastName()]);
                    $vote = $this->getVoteString($schedulingTimeUser->getAccept());

                    if (!isset($votings['user'][$user->getId()])) {
                        $votings['user'][$user->getId()] = [
                            'Name' => $name,
                            'Email' => $user->getEmail(),
                        ];
                    }

                    $votings['user'][$user->getId()][$schedulingTimeString] = $vote;
                }
            }
        }

        return $votings;
    }

    private function fillAllVotings(array $userVotings, array $times): array
    {
        $filledUpVotings = [];
        sort($times);

        foreach ($userVotings as $userVoting) {
            $filledUpVoting = [
                'Name' => $userVoting['Name'],
                'Email' => $userVoting['Email'],
            ];

            foreach ($times as $time) {
                if (isset($userVoting[$time])) {
                    $filledUpVoting[$time] = $userVoting[$time];
                } else {
                    $filledUpVoting[$time] = 'null';
                }
            }

            $filledUpVotings[] = $filledUpVoting;
        }

        return $filledUpVotings;
    }
}
