<?php

namespace App\Controller;

use App\Service\Theme\ThemeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/room/theme', name: 'app_theme_')]
class ThemeController extends AbstractController
{
    public function __construct(
        private readonly ThemeService $themeService,
    )
    {
    }

    #[Route('/overview', name: 'overview', methods: ['GET'])]
    public function showThemes(): Response
    {
        $applicationProperties = $this->themeService->getApplicationProperties('SECURITY_ALLLOW_UPLOAD_THEME_GROUP');
        if ($applicationProperties !== '') {
            $groups = $this->getUser()->getGroups();
            if (!$groups || !in_array($applicationProperties, $groups)) {
                $this->addFlash('danger', 'Permission denied');

                return $this->redirectToRoute('index');
            }
        }

        $themes = $this->themeService->getAllThemes();
        return $this->render('theme/overview.html.twig', [
            'themes' => $themes,
            'now' => new \DateTimeImmutable('today'),
        ]);
    }
}
