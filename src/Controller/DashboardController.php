<?php

/**
 * Created by PhpStorm.
 * User: andreas.holzmann
 * Date: 15.05.2020
 * Time: 09:15
 */

namespace App\Controller;

use App\Form\Type\SecondEmailType;
use App\Helper\JitsiAdminController;
use App\Repository\ServerRepository;
use App\Service\analytics\AnalyticsService;
use App\Service\Dashboard\DashboardViewService;
use App\Service\FavoriteService;
use App\Service\ServerUserManagment;
use App\Service\TermsAndConditions\TermsAndConditionsService;
use App\Service\Theme\ThemeService;
use App\Service\UserCreatorService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
        DashboardViewService         $dashboardViewService,
    ): Response
    {
        if (!$termsAndConditionsService->hasAcceptedTerms($this->getUser())) {
            return $this->redirectToRoute('app_terms_and_conditions');
        }
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

        // The complete dashboard dataset (rooms, status maps, capabilities, urls,
        // translated labels, feature flags) is serialised by the view service and
        // bootstrapped into the Twig shell as JSON. React hydrates from it.
        $dashboardState = $dashboardViewService->buildInitialState($this->getUser());

        if ($request->get('snack')) {
            if ($request->get('color')) {
                $this->addFlash($request->get('color'), $request->get('snack'));
            }
        }
        $form = $this->createForm(
            SecondEmailType::class,
            $this->getUser(),
            [
                'action' => $this->generateUrl('second_email_save'),
            ],
        );
        $form->remove('profilePicture');
        $servers = $serverUserManagment->getServersFromUser($this->getUser());
        $publicServer = $this->serverRepository->find($this->themeService->getApplicationProperties('PUBLIC_SERVER'));

        $res = $this->render(
            'dashboard/index.html.twig',
            [
                'secondEmailForm' => $form->createView(),
                'servers' => $servers,
                'publicServer' => $publicServer,
                'doAllowUserCreation' => $this->userCreatorService->doAllowUserCreation(),
                'dashboardState' => $dashboardState,
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
