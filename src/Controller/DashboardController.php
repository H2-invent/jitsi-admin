<?php

/**
 * Created by PhpStorm.
 * User: andreas.holzmann
 * Date: 15.05.2020
 * Time: 09:15
 */

namespace App\Controller;

use App\Entity\Rooms;
use App\Form\Type\SecondEmailType;
use App\Helper\JitsiAdminController;
use App\Repository\RoomsRepository;
use App\Repository\SchedulingTimeUserRepository;
use App\Repository\ServerRepository;
use App\Service\analytics\AnalyticsService;
use App\Service\DashboardService;
use App\Service\FavoriteService;
use App\Service\ServerUserManagment;
use App\Service\TermsAndConditions\TermsAndConditionsService;
use App\Service\Theme\ThemeService;
use App\Service\UserCreatorService;
use App\Service\webhook\RoomStatusFrontendService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class DashboardController
 * @package App\Controller
 */
class DashboardController extends JitsiAdminController
{
    public function __construct(
        ManagerRegistry $managerRegistry,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        ParameterBagInterface $parameterBag,
        private ThemeService $themeService,
        private ServerRepository $serverRepository,
        private UserCreatorService $userCreatorService,
    )
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
    }

    private function initializeUserFields(): void
    {
        $user = $this->getUser();
        $em = $this->doctrine->getManager();
        $changed = false;

        if (!$user->getUid()) {
            $user->setUid(md5(uniqid()));
            $changed = true;
        }
        if (!$user->getOwnRoomUid()) {
            $user->setOwnRoomUid(md5(uniqid()));
            $changed = true;
        }
        if (!$user->getTimezone()) {
            $user->setTimezone(date_default_timezone_get());
            $changed = true;
        }

        if ($changed) {
            $em->persist($user);
            $em->flush();
        }
    }

    /**
     * @param Request $request
     * @return RedirectResponse|Response
     */
    #[Route(path: '/room/dashboard', name: 'dashboard')]
    public function dashboard(
        Request                      $request,
        ServerUserManagment          $serverUserManagment,
        ParameterBagInterface        $parameterBag,
        FavoriteService              $favoriteService,
        TermsAndConditionsService    $termsAndConditionsService,
        AnalyticsService             $analyticsService,
        RoomStatusFrontendService    $roomStatusFrontendService,
        DashboardService             $dashboardService,
        SchedulingTimeUserRepository $schedulingTimeUserRepository,
    ): Response
    {
        if (!$termsAndConditionsService->hasAcceptedTerms($this->getUser())) {
            return $this->redirectToRoute('app_terms_and_conditions');
        }
        $stopwatch = new Stopwatch();
        $start = $stopwatch->start('dashboard');
        if ($request->get('join_room') && $request->get('type')) {
            return $this->redirectToRoute(
                'room_join',
                [
                    'room' => $request->get('join_room'),
                    't' => $request->get('type'),
                ],
            );
        }

        $this->initializeUserFields();
        $favoriteService->cleanFavorites($this->getUser());

        $user = $this->getUser();
        $roomRepo = $this->doctrine->getRepository(Rooms::class);
        $pageSize = RoomsRepository::getPageSize();

        // Every list on the dashboard is loaded page by page (and extended lazily on scroll)
        // so that both load time and memory usage stay constant no matter how many rooms
        // exist in the database.
        $futureRooms = $roomRepo->findRoomsInFuture($user, null);
        $futureHasMore = count($futureRooms) > $pageSize;
        $futureRooms = array_slice($futureRooms, 0, $pageSize);
        $roomsFuture = $this->groupRoomsByDay($futureRooms, $user);
        $futureLastRoomId = $roomsFuture ? $this->lastRoomId(end($roomsFuture)) : null;
        $futureLastDate = $roomsFuture ? array_key_last($roomsFuture) : null;

        $scheduledRooms = $roomRepo->findScheduledRooms($user, null);
        $scheduledHasMore = count($scheduledRooms) > $pageSize;
        $scheduledRooms = array_slice($scheduledRooms, 0, $pageSize);
        $scheduledLastRoomId = $this->lastRoomId($scheduledRooms);

        $persistantRooms = $roomRepo->getMyPersistantRooms($user, null);
        $persistantHasMore = count($persistantRooms) > $pageSize;
        $persistantRooms = array_slice($persistantRooms, 0, $pageSize);
        $persistantLastRoomId = $this->lastRoomId($persistantRooms);

        $roomsPast = $roomRepo->findRoomsInPast($user, null);
        $pastHasMore = count($roomsPast) > $pageSize;
        $roomsPast = array_slice($roomsPast, 0, $pageSize);
        $pastLastRoomId = $this->lastRoomId($roomsPast);

        $roomsNow = $roomRepo->findRuningRooms($user);
        $roomsToday = $roomRepo->findTodayRooms($user);

        $roomIds = [];
        foreach ([$futureRooms, $scheduledRooms, $persistantRooms, $roomsPast, $roomsNow] as $rooms) {
            foreach ($rooms as $room) {
                $roomIds[] = $room->getId();
            }
        }

        $servers = $serverUserManagment->getServersFromUser($user);
        $today = (new \DateTime('now'))->setTimezone(new \DateTimeZone($user->getTimeZone()));
        $tomorrow = (clone $today)->modify('+1day');
        $favorites = $roomRepo->findFavoriteRooms($user);
        foreach ($favorites as $room) {
            $roomIds[] = $room->getId();
        }

        $uniqueRoomIds = array_unique($roomIds);
        $roomStatusOpenMap = $roomStatusFrontendService->getRoomCreatedStatusMap($uniqueRoomIds);
        $roomStatusOccupantsMap = $roomStatusFrontendService->getRoomOccupantsMap($uniqueRoomIds);
        $roomStatusClosedMap = $roomStatusFrontendService->getRoomClosedStatusMap($uniqueRoomIds);
        $roomHasStatusMap = $roomStatusFrontendService->getRoomHasStatusMap($uniqueRoomIds);

        $allDisplayedRooms = array_merge($futureRooms, $scheduledRooms, $persistantRooms, $roomsPast, $favorites);
        $roomClosedForStartMap = $dashboardService->getRoomClosedForStartMap(
            $allDisplayedRooms,
            $user,
            $roomStatusOpenMap
        );

        $scheduleUserHasVotedMap = $schedulingTimeUserRepository->findVotesForUserAndRooms(
            $user,
            array_unique($roomIds)
        );

        $timer = $stopwatch->stop('dashboard');
        if ($request->get('snack')) {
            if ($request->get('color')) {
                $this->addFlash($request->get('color'), $request->get('snack'));
            }
        }
        $date = new \DateTime();
        $timestamp = $date->getTimestamp();
        $form = $this->createForm(
            SecondEmailType::class,
            $this->getUser(),
            [
                'action' => $this->generateUrl('second_email_save'),
            ],
        );
        $publicServer = $this->serverRepository->find($this->themeService->getApplicationProperties('PUBLIC_SERVER'));

        $form->remove('profilePicture');
        $res = $this->render(
            'dashboard/index.html.twig',
            [
                'secondEmailForm' => $form->createView(),
                'roomsFuture' => $roomsFuture,
                'futureHasMore' => $futureHasMore,
                'futureLastRoomId' => $futureLastRoomId,
                'futureLastDate' => $futureLastDate,
                'roomsPast' => $roomsPast,
                'pastHasMore' => $pastHasMore,
                'pastLastRoomId' => $pastLastRoomId,
                'runningRooms' => $roomsNow,
                'persistantRooms' => $persistantRooms,
                'persistantHasMore' => $persistantHasMore,
                'persistantLastRoomId' => $persistantLastRoomId,
                'todayRooms' => $roomsToday,
                'servers' => $servers,
                'today' => $today,
                'tomorrow' => $tomorrow,
                'favorite' => $favorites,
                'scheduledRooms' => $scheduledRooms,
                'scheduledHasMore' => $scheduledHasMore,
                'scheduledLastRoomId' => $scheduledLastRoomId,
                'roomStatusOpenMap' => $roomStatusOpenMap,
                'roomStatusOccupantsMap' => $roomStatusOccupantsMap,
                'roomStatusClosedMap' => $roomStatusClosedMap,
                'roomHasStatusMap' => $roomHasStatusMap,
                'roomClosedMapForStart' => $roomClosedForStartMap,
                'scheduleUserHasVotedMap' => $scheduleUserHasVotedMap,
                'timestamp' => $timestamp,
                'time' => $timer->getDuration(),
                'publicServer' => $publicServer,
                'doAllowUserCreation' => $this->userCreatorService->doAllowUserCreation(),
            ],
        );
        $analyticsService->sendAnalytics();
        if ($parameterBag->get('laf_darkmodeAsDefault') && !$request->cookies->has('DARK_MODE')) {
            $res = $this->redirectToRoute('dashboard');
            $res->headers->setCookie(
                Cookie::create(
                    'DARK_MODE',
                    1,
                    time() + (2 * 365 * 24 * 60 * 60),
                    '/',       // Path.
                    null,    // Domain.
                    false,   // Xmit secure https.
                    false   // HttpOnly Flag
                )
            );
        }
        if (!$request->isXmlHttpRequest()) {
            if ($this->themeService->getApplicationProperties('SECURITY_ALLLOW_UPLOAD_THEME_GROUP') !== '') {
                $groups = $this->getUser()->getGroups();
                if ($groups && in_array($this->themeService->getApplicationProperties('SECURITY_ALLLOW_UPLOAD_THEME_GROUP'), $groups)) {
                    $this->themeService->checkRemainingDays();
                }
            } else {
                $this->themeService->checkRemainingDays();
            }


        }
        $res->headers->setCookie(
            Cookie::create(
                'is_loggedIn_user',
                1,
                time() + (2 * 365 * 24 * 60 * 60),
                '/',  // Path.
            )
        );
        return $res;
    }

    /**
     * @param Request $request
     * @return RedirectResponse|Response
     */
    #[Route(
        path: '/room/dashboard/lazy/{type}/{lastRoomId}',
        name: 'dashboard_lazy',
        requirements: ['lastRoomId' => '\d+'],
        defaults: ['lastRoomId' => 0]
    )]
    public function dashboardLayzLoad(
        Request                     $request,
        ServerUserManagment         $serverUserManagment,
        DashboardService            $dashboardService,
        RoomStatusFrontendService   $roomStatusFrontendService,
        SchedulingTimeUserRepository $schedulingTimeUserRepository,
        $type,
        $lastRoomId = 0
    ) {
        $user = $this->getUser();
        $roomRepo = $this->doctrine->getRepository(Rooms::class);
        $pageSize = RoomsRepository::getPageSize();
        $cursor = $lastRoomId > 0 ? (int)$lastRoomId : null;

        if ($type === 'future') {
            $rooms = $roomRepo->findRoomsInFuture($user, $cursor);
            $hasMore = count($rooms) > $pageSize;
            $rooms = array_slice($rooms, 0, $pageSize);
            $roomsFuture = $this->groupRoomsByDay($rooms, $user);
            $lastId = $roomsFuture ? $this->lastRoomId(end($roomsFuture)) : null;
            $lastDate = $roomsFuture ? array_key_last($roomsFuture) : null;

            return $this->render('dashboard/__lazyFuture.html.twig', array_merge(
                $this->buildLazyContext($rooms, $serverUserManagment, $dashboardService, $roomStatusFrontendService, $schedulingTimeUserRepository),
                [
                    'roomsFuture' => $roomsFuture,
                    'futureHasMore' => $hasMore,
                    'futureLastRoomId' => $lastId,
                    'futureLastDate' => $lastDate,
                    'lastDate' => $request->query->get('lastDate'),
                    'today' => (new \DateTime('now'))->setTimezone(new \DateTimeZone($user->getTimeZone())),
                    'tomorrow' => (new \DateTime('now'))->setTimezone(new \DateTimeZone($user->getTimeZone()))->modify('+1day'),
                ]
            ));
        }

        if ($type === 'scheduled') {
            $rooms = $roomRepo->findScheduledRooms($user, $cursor);
            $hasMore = count($rooms) > $pageSize;
            $rooms = array_slice($rooms, 0, $pageSize);
            $lastId = $this->lastRoomId($rooms);

            return $this->render('dashboard/__lazyScheduled.html.twig', array_merge(
                $this->buildLazyContext($rooms, $serverUserManagment, $dashboardService, $roomStatusFrontendService, $schedulingTimeUserRepository),
                [
                    'scheduledRooms' => $rooms,
                    'scheduledHasMore' => $hasMore,
                    'scheduledLastRoomId' => $lastId,
                    'lastSection' => $request->query->get('lastSection') === '1',
                ]
            ));
        }

        if ($type === 'fixed') {
            $rooms = $roomRepo->getMyPersistantRooms($user, $cursor);
            $hasMore = count($rooms) > $pageSize;
            $rooms = array_slice($rooms, 0, $pageSize);
            $lastId = $this->lastRoomId($rooms);

            return $this->render('dashboard/__lazyFixed.html.twig', array_merge(
                $this->buildLazyContext($rooms, $serverUserManagment, $dashboardService, $roomStatusFrontendService, $schedulingTimeUserRepository),
                [
                    'persistantRooms' => $rooms,
                    'persistantHasMore' => $hasMore,
                    'persistantLastRoomId' => $lastId,
                ]
            ));
        }

        if ($type === 'past') {
            $rooms = $roomRepo->findRoomsInPast($user, $cursor);
            $hasMore = count($rooms) > $pageSize;
            $rooms = array_slice($rooms, 0, $pageSize);
            $lastId = $this->lastRoomId($rooms);

            return $this->render('dashboard/__lazyPast.html.twig', array_merge(
                $this->buildLazyContext($rooms, $serverUserManagment, $dashboardService, $roomStatusFrontendService, $schedulingTimeUserRepository),
                [
                    'roomsPast' => $rooms,
                    'pastHasMore' => $hasMore,
                    'pastLastRoomId' => $lastId,
                ]
            ));
        }

        return new JsonResponse(['error' => true]);
    }

    /**
     * Groups rooms by the day they start (in the user's timezone), sorted ascending.
     *
     * @param Rooms[] $rooms
     * @return array<string, Rooms[]>
     */
    private function groupRoomsByDay(array $rooms, \App\Entity\User $user): array
    {
        $grouped = [];
        foreach ($rooms as $room) {
            if (!$room->getStartUtc()) {
                continue;
            }
            $grouped[$room->getStartwithTimeZone($user)->format('Ymd')][] = $room;
        }
        ksort($grouped);

        return $grouped;
    }

    /**
     * Returns the id of the last room in the array, or null for an empty array.
     *
     * @param Rooms[] $rooms
     */
    private function lastRoomId(array $rooms): ?int
    {
        $rooms = array_values($rooms);
        $last = end($rooms);

        return $last ? $last->getId() : null;
    }

    /**
     * Builds the shared template context (status maps, vote map, servers) for a lazily
     * loaded page of rooms. All queries are bounded by the number of rooms in $rooms.
     *
     * @param Rooms[] $rooms
     */
    private function buildLazyContext(
        array                       $rooms,
        ServerUserManagment         $serverUserManagment,
        DashboardService            $dashboardService,
        RoomStatusFrontendService   $roomStatusFrontendService,
        SchedulingTimeUserRepository $schedulingTimeUserRepository
    ): array {
        $roomIds = array_values(array_unique(array_map(static fn(Rooms $room) => $room->getId(), $rooms)));

        $roomStatusOpenMap = $roomStatusFrontendService->getRoomCreatedStatusMap($roomIds);
        $roomStatusOccupantsMap = $roomStatusFrontendService->getRoomOccupantsMap($roomIds);
        $roomStatusClosedMap = $roomStatusFrontendService->getRoomClosedStatusMap($roomIds);
        $roomHasStatusMap = $roomStatusFrontendService->getRoomHasStatusMap($roomIds);
        $roomClosedMapForStart = $dashboardService->getRoomClosedForStartMap($rooms, $this->getUser(), $roomStatusOpenMap);
        $scheduleUserHasVotedMap = $schedulingTimeUserRepository->findVotesForUserAndRooms($this->getUser(), $roomIds);

        return [
            'servers' => $serverUserManagment->getServersFromUser($this->getUser()),
            'timestamp' => (new \DateTime())->getTimestamp(),
            'roomStatusOpenMap' => $roomStatusOpenMap,
            'roomStatusOccupantsMap' => $roomStatusOccupantsMap,
            'roomStatusClosedMap' => $roomStatusClosedMap,
            'roomHasStatusMap' => $roomHasStatusMap,
            'roomClosedMapForStart' => $roomClosedMapForStart,
            'scheduleUserHasVotedMap' => $scheduleUserHasVotedMap,
        ];
    }

    #[Route(path: '/room/dashboard/adressbook-fragment', name: 'dashboard_adressbook_fragment')]
    public function adressbookFragment(ServerUserManagment $serverUserManagment): Response
    {
        $servers = $serverUserManagment->getServersFromUser($this->getUser());
        return $this->render('addressbook/__addressBook.html.twig', [
            'servers' => $servers,
            'doAllowUserCreation' => $this->userCreatorService->doAllowUserCreation(),
        ]);
    }
}
