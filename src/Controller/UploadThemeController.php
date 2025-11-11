<?php

namespace App\Controller;

use App\Form\Type\ThemeUploadType;
use App\Service\Result\Error\ThemeUploadError;
use App\Service\Theme\ThemeService;
use App\Service\Theme\ThemeUploadService;
use App\Twig\Theme;
use H2Entwicklung\Signature\CheckSignature;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/room/upload/theme/', name: 'app_upload_theme_')]
class UploadThemeController extends AbstractController
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private ThemeService $themeService,
        private ThemeUploadService $themeUploadService,
    )
    {
    }

    #[Route('form', name: 'form', methods: ['GET'])]
    public function index(): Response
    {
        if ($this->themeService->getApplicationProperties('SECURITY_ALLLOW_UPLOAD_THEME_GROUP') !== '') {
            $groups = $this->getUser()->getGroups();
            if (!in_array($this->themeService->getApplicationProperties('SECURITY_ALLLOW_UPLOAD_THEME_GROUP'),
                $groups
            )) {
                $this->addFlash('danger', 'Permission denied');

                return $this->redirectToRoute('index');
            }
        }
        $form = $this->createForm(ThemeUploadType::class,
            null,
            ['action' => $this->urlGenerator->generate('app_upload_theme_save')]
        );

        return $this->render('upload_theme/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('save', name: 'save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $form = $this->createForm(ThemeUploadType::class);
        $form->handleRequest($request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('danger', 'Please upload a zip file');
            return $this->redirectToRoute('app_upload_theme_form');
        }

        /** @var UploadedFile $themeFile */
        $themeFile = $form->get('theme')->getData();
        // this condition is needed because the 'brochure' field is not required
        // so the PDF file must be processed only when a file is uploaded
        if (!$themeFile) {
            $this->addFlash('danger', 'No Theme uploaded');
            return $this->redirectToRoute('app_upload_theme_form');
        }

        try {
            $uploadThemeResult = $this->themeUploadService->uploadTheme($themeFile);
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
            return $this->redirectToRoute('app_upload_theme_form');
        }

        if (!$uploadThemeResult->isSuccess()) {
            $errorMessage = $uploadThemeResult->getErrorType()->value;

            $this->addFlash('danger', $errorMessage);
            return $this->redirectToRoute('app_upload_theme_form');
        }

        $this->addFlash('success', 'Theme successfully uploaded');
        return $this->redirectToRoute('app_upload_theme_form');

    }
}
