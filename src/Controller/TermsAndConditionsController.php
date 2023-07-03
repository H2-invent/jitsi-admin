<?php

namespace App\Controller;

use App\Entity\User;
use App\Helper\JitsiAdminController;
use App\Service\TermsAndConditions\TermsAndConditionsService;
use App\Service\ThemeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TermsAndConditionsController extends JitsiAdminController
{
    #[Route('/room/terms/and/conditions', name: 'app_terms_and_conditions')]
    public function index(ThemeService $themeService, TermsAndConditionsService $termsAndConditionsService): Response
    {
        if (!$termsAndConditionsService->hasAcceptedTerms($this->getUser())) {
            return $this->render(
                'terms_and_conditions/index.html.twig',
                [
                    'server' => null,
                ]
            );
        }
        return $this->redirectToRoute('dashboard');
    }

    #[Route('/room/terms/and/conditions/accept', name: 'app_terms_and_conditions_accept')]
    public function accept(TermsAndConditionsService $termsAndConditionsService): Response
    {
        $termsAndConditionsService->acceptTerms($this->getUser());
        return $this->redirectToRoute('dashboard');
    }
}
