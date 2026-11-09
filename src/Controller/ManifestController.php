<?php

namespace App\Controller;

use App\Helper\JitsiAdminController;
use App\Service\Theme\ThemeService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class ManifestController extends JitsiAdminController
{
    public function __construct(
        private readonly ThemeService $themeService,
        ManagerRegistry               $managerRegistry,
        TranslatorInterface           $translator,
        LoggerInterface               $logger,
        ParameterBagInterface         $parameterBag
    ) {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: '/site.webmanifest', name: 'app_manifest')]
    public function index(): Response
    {
        $url             = '/room/dashboard';
        $favicon         = $this->themeService->getThemeProperty('icon');
        $favicon         = $favicon ?: 'favicon-large.png';
        $backgroundColor = $this->themeService->getThemeProperty('primaryColor');
        $title           = $this->themeService->getThemeProperty('title');
        $ending          = explode('.', $favicon);
        $ending          = $ending[sizeof($ending) - 1];


        $res = [
            "short_name"       => $title ?: "Jitsi-Admin",
            "name"             => $title ?: "Jitsi-Admin",
            "dir"              => "ltr",
            "icons"            => [
                [
                    "src"   => $favicon,
                    "type"  => 'image/' . $ending,
                    "sizes" => "100x100"
                ],
                [
                    "src"   => $favicon,
                    "type"  => 'image/' . $ending,
                    "sizes" => "512x512"
                ]
            ],
            "start_url"        => $url,
            "display"          => "standalone",
            "background_color" => $backgroundColor ?: '#2561ef',
            "theme_color"      => $backgroundColor ?: '#2561ef',
            "orientation"      => "any"
        ];

        return new JsonResponse($res);
    }
}
