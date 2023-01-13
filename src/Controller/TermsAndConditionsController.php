<?php

namespace App\Controller;

use App\Entity\User;
use App\Helper\JitsiAdminController;
use App\Service\ThemeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TermsAndConditionsController extends JitsiAdminController
{
    #[Route('/room/terms/and/conditions', name: 'app_terms_and_conditions')]
    public function index(ThemeService $themeService): Response
    {
        if (!$this->getUser()->isAcceptTermsAndConditions()) {
            if ($themeService->getApplicationProperties('LAF_TERMS_AND_CONDITIONS') === '') {
                $user = $this->getUser();
                $user->setAcceptTermsAndConditions(true);
                $em = $this->doctrine->getManager();
                $em->persist($user);
                $em->flush();
                return $this->redirectToRoute('dashboard');
            }
            return $this->render('terms_and_conditions/index.html.twig', [
               'server'=>null,
            ]);
        }
        return $this->redirectToRoute('dashboard');
    }

    #[Route('/room/terms/and/conditions/accept', name: 'app_terms_and_conditions_accept')]
    public function accept(ThemeService $themeService): Response
    {
        if (!$this->getUser()->isAcceptTermsAndConditions()) {
            $user = $this->getUser();
            $user->setAcceptTermsAndConditions(true);
            $em = $this->doctrine->getManager();
            $em->persist($user);
            $em->flush();
        }
        return $this->redirectToRoute('dashboard');
    }
}
