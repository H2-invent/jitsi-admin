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
use App\Repository\SchedulingTimeUserRepository;
use App\Repository\ServerRepository;
use App\Service\analytics\AnalyticsService;
use App\Service\DashboardService;
use App\Service\FavoriteService;
use App\Service\ServerUserManagment;
use App\Service\TermsAndConditions\TermsAndConditionsService;
use App\Service\Theme\ThemeService;
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

        $allRooms = $this->doctrine->getRepository(Rooms::class)->findRoomsForDashboard($this->getUser());
        [
            'roomsFuture'     => $roomsFuture,
            'roomsNow'        => $roomsNow,
            'roomsToday'      => $roomsToday,
            'persistantRooms' => $persistantRooms,
            'scheduledRooms'  => $scheduledRooms,
            'roomIds'         => $roomIds,
        ] = $dashboardService->categorizeRooms($allRooms, $this->getUser());

        $roomsPast = $this->doctrine->getRepository(Rooms::class)->findRoomsInPast($this->getUser(), 0);
        foreach ($roomsPast as $room) {
            $roomIds[] = $room->getId();
        }

        $servers = $serverUserManagment->getServersFromUser($this->getUser());
        $today = (new \DateTime('now'))->setTimezone(new \DateTimeZone($this->getUser()->getTimeZone()));
        $tomorrow = (clone $today)->modify('+1day');
        $favorites = $this->doctrine->getRepository(Rooms::class)->findFavoriteRooms($this->getUser());
        foreach ($favorites as $room) {
            $roomIds[] = $room->getId();
        }

        $roomStatusOpenMap = $roomStatusFrontendService->getRoomCreatedStatusMap(array_unique($roomIds));
        $roomStatusOccupantsMap = $roomStatusFrontendService->getRoomOccupantsMap(array_unique($roomIds));
        $roomStatusClosedMap = $roomStatusFrontendService->getRoomClosedStatusMap(array_unique($roomIds));

        $allDisplayedRooms = array_merge($allRooms, $roomsPast, $favorites);
        $roomClosedForStartMap = $dashboardService->getRoomClosedForStartMap(
            $allDisplayedRooms,
            $this->getUser(),
            $roomStatusOpenMap
        );

        $scheduleUserHasVotedMap = $schedulingTimeUserRepository->findVotesForUserAndRooms(
            $this->getUser(),
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
                'roomsPast' => $roomsPast,
                'runningRooms' => $roomsNow,
                'persistantRooms' => $persistantRooms,
                'todayRooms' => $roomsToday,
                'servers' => $servers,
                'today' => $today,
                'tomorrow' => $tomorrow,
                'favorite' => $favorites,
                'scheduledRooms' => $scheduledRooms,
                'roomStatusOpenMap' => $roomStatusOpenMap,
                'roomStatusOccupantsMap' => $roomStatusOccupantsMap,
                'roomStatusClosedMap' => $roomStatusClosedMap,
                'roomClosedMapForStart' => $roomClosedForStartMap,
                'scheduleUserHasVotedMap' => $scheduleUserHasVotedMap,
                'timestamp' => $timestamp,
                'time' => $timer->getDuration(),
                'publicServer' => $publicServer
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
    #[Route(path: '/room/dashboard/lazy/{type}/{offset}', name: 'dashboard_lazy')]
    public function dashboardLayzLoad(Request $request, ServerUserManagment $serverUserManagment, ParameterBagInterface $parameterBag, FavoriteService $favoriteService, $type, $offset)
    {
        $servers = $serverUserManagment->getServersFromUser($this->getUser());
        if ($type === 'fixed') {
            $persistantRooms = $this->doctrine->getRepository(Rooms::class)->getMyPersistantRooms($this->getUser(), $offset);
            return $this->render(
                'dashboard/__lazyFixed.html.twig',
                [
                    'persistantRooms' => $persistantRooms,
                    'servers' => $servers,
                    'offset' => $offset
                ]
            );
        } elseif ($type === 'past') {
            $roomsPast = $this->doctrine->getRepository(Rooms::class)->findRoomsInPast($this->getUser(), $offset);
            return $this->render(
                'dashboard/__lazyPast.html.twig',
                [
                    'roomsPast' => $roomsPast,
                    'servers' => $servers,
                    'offset' => $offset
                ]
            );
        }

        return new JsonResponse(['error' => true]);
    }
}
