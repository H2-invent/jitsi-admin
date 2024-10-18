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
use App\Service\analytics\AnalyticsService;
use App\Service\FavoriteService;
use App\Service\ServerUserManagment;
use App\Service\TermsAndConditions\TermsAndConditionsService;
use App\Service\ThemeService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class DashboardController
 * @package App\Controller
 */
class DashboardController extends JitsiAdminController
{
    public function __construct(ManagerRegistry $managerRegistry, TranslatorInterface $translator, LoggerInterface $logger, ParameterBagInterface $parameterBag, private ThemeService $themeService)
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
    }


    /**
     * @param Request $request
     * @return RedirectResponse|Response
     */
    #[Route(path: '/room/dashboard', name: 'dashboard')]
    public function dashboard(
        Request                   $request,
        ServerUserManagment       $serverUserManagment,
        ParameterBagInterface     $parameterBag,
        FavoriteService           $favoriteService,
        TermsAndConditionsService $termsAndConditionsService,
        AnalyticsService          $analyticsService,
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
        $roomsFuture = $this->doctrine->getRepository(Rooms::class)->findRoomsInFuture($this->getUser());

        $r = [];
        $future = [];
        foreach ($roomsFuture as $data) {
            $future[$data->getStartwithTimeZone($this->getUser())->format('Ymd')][] = $data;
        }

        $em = $this->doctrine->getManager();
        if (!$this->getUser()->getUid()) {
            $user = $this->getUser();
            $user->setUid(md5(uniqid()));

            $em->persist($user);
            $em->flush();
        }
        if (!$this->getUser()->getOwnRoomUid()) {
            $user = $this->getUser();
            $user->setOwnRoomUid(md5(uniqid()));

            $em->persist($user);
            $em->flush();
        }
        if (!$this->getUser()->getTimezone()) {
            $user = $this->getUser();
            $user->setTimezone(date_default_timezone_get());
            $em->persist($user);
            $em->flush();
        }
        $favoriteService->cleanFavorites($this->getUser());
        $roomsPast = $this->doctrine->getRepository(Rooms::class)->findRoomsInPast($this->getUser(), 0);
        $roomsNow = $this->doctrine->getRepository(Rooms::class)->findRuningRooms($this->getUser());
        $roomsToday = $this->doctrine->getRepository(Rooms::class)->findTodayRooms($this->getUser());
        $persistantRooms = $this->doctrine->getRepository(Rooms::class)->getMyPersistantRooms($this->getUser(), 0);
        $servers = $serverUserManagment->getServersFromUser($this->getUser());
        $today = (new \DateTime('now'))->setTimezone(new \DateTimeZone($this->getUser()->getTimeZone()));
        $tomorrow = (clone $today)->modify('+1day');
        $favorites = $this->doctrine->getRepository(Rooms::class)->findFavoriteRooms($this->getUser());
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
        $form->remove('profilePicture');
        $res = $this->render(
            'dashboard/index.html.twig',
            [
                'secondEmailForm' => $form->createView(),
                'roomsFuture' => $future,
                'roomsPast' => $roomsPast,
                'runningRooms' => $roomsNow,
                'persistantRooms' => $persistantRooms,
                'todayRooms' => $roomsToday,
                'servers' => $servers,
                'today' => $today,
                'tomorrow' => $tomorrow,
                'favorite' => $favorites,
                'timestamp' => $timestamp,
                'time' => $timer->getDuration(),
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
                    '/',      // Path.
                    null,     // Domain.
                    false,    // Xmit secure https.
                    false     // HttpOnly Flag
                )
            );
        }
        if (!$request->isXmlHttpRequest()) {
            if ($this->themeService->getApplicationProperties('SECURITY_ALLLOW_UPLOAD_THEME_GROUP') !== '') {
                $groups = $this->getUser()->getGroups();
                if (in_array($this->themeService->getApplicationProperties('SECURITY_ALLLOW_UPLOAD_THEME_GROUP'), $groups)) {
                    $this->themeService->checkRemainingDays();
                }
            } else {
                $this->themeService->checkRemainingDays();
            }


        }
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
