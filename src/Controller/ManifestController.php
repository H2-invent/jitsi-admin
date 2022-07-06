<?php

namespace App\Controller;

use App\Helper\JitsiAdminController;
use App\Service\CreateHttpsUrl;
use App\Service\ThemeService;
use Doctrine\Persistence\ManagerRegistry;
use Imagine\Gd\Image;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ManifestController extends JitsiAdminController
{
    private ThemeService $themeService;
    private CreateHttpsUrl $createHttpsUrl;

    public function __construct(CreateHttpsUrl $createHttpsUrl, ThemeService $themeService, ManagerRegistry $managerRegistry, TranslatorInterface $translator, LoggerInterface $logger, ParameterBagInterface $parameterBag)
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
        $this->themeService = $themeService;
        $this->createHttpsUrl = $createHttpsUrl;

    }

    /**
     * @Route("/site.webmanifest", name="app_manifest")
     */
    public function index(): Response
    {
        $url = '/room/dashboard';
        $favicon = $this->themeService->getThemeProperty('icon');
        $favicon = $favicon ?: 'favicon-large.ico';
        $backgroundColor = $this->themeService->getThemeProperty('primaryColor');
        $title = $this->themeService->getThemeProperty('title');
        $ending = explode('.', $favicon);
        $ending = $ending[sizeof($ending) - 1];


        $res = array(
            "short_name" => $title ?: "Jitsi-Admin",
            "name" => $title ?: "Jitsi-Admin",
            "dir" => "ltr",
            "icons" => array(
                array(
                    "src" => $favicon,
                    "type" =>'image/' . $ending,
                    "sizes" => "100x100"
                ),
                array(
                    "src" => $favicon,
                    "type" => 'image/' . $ending,
                    "sizes" => "512x512"
                )
            ),
            "start_url" => $url,
            "display" => "standalone",
            "background_color" => $backgroundColor ?: '#2561ef',
            "theme_color" => $backgroundColor ?: '#2561ef',
            "orientation" => "portrait"
        );

        return new JsonResponse($res);
    }

}
