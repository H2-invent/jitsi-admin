<?php

namespace App\Controller;

use App\Helper\JitsiAdminController;
use App\Service\CreateHttpsUrl;
use App\Service\ThemeService;
use Doctrine\Persistence\ManagerRegistry;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoginControllerKeycloak extends JitsiAdminController
{
    private ThemeService $themeService;

    public function __construct(
        ThemeService           $themeService,
        ManagerRegistry        $managerRegistry,
        TranslatorInterface    $translator,
        LoggerInterface        $logger,
        ParameterBagInterface  $parameterBag,
        private CreateHttpsUrl $createHttpsUrl)
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
        $this->themeService = $themeService;
    }

    #[Route(path: '/login', name: 'login_keycloak')]
    public function index(ClientRegistry $clientRegistry): Response
    {

        $options = ['redirect_uri' => $this->createHttpsUrl->replaceSchemeOfAbsolutUrl($this->generateUrl('connect_keycloak_check',[],UrlGenerator::ABSOLUTE_URL))];

        if ($this->themeService->getThemeProperty('idp_provider')) {
            $options['kc_idp_hint'] = $this->themeService->getThemeProperty('idp_provider');
        }
        $res = $clientRegistry->getClient('keycloak_main')->redirect(['email', 'openid', 'profile'], $options);
        return $res;
    }


    #[Route(path: '/register', name: 'register_keycloak')]
    public function register(ClientRegistry $clientRegistry, CreateHttpsUrl $createHttpsUrl): Response
    {
        $options = ['redirect_uri' => $this->createHttpsUrl->replaceSchemeOfAbsolutUrl($this->generateUrl('connect_keycloak_check',[],UrlGenerator::ABSOLUTE_URL))];

        $url = $this->getParameter('KEYCLOAK_URL') . '/realms/' . $this->getParameter('KEYCLOAK_REALM') . '/protocol/openid-connect/registrations?client_id=' .
            $this->getParameter('KEYCLOAK_ID') .
            '&response_type=code&scope=openid email&redirect_uri=' . $this->createHttpsUrl->replaceSchemeOfAbsolutUrl($this->generateUrl('connect_keycloak_check',[],UrlGenerator::ABSOLUTE_URL)) . '&kc_locale=de';
        return $this->redirect($url);
    }


    public function check(ClientRegistry $clientRegistry, Request $request)
    {
    }

    #[Route(path: '/login/keycloak_edit', name: 'connect_keycloak_edit')]
    public function edit(ClientRegistry $clientRegistry, Request $request, ThemeService $themeService)
    {
        $url = $this->getParameter('KEYCLOAK_URL');
        if ($this->themeService->getThemeProperty('idp_provider')) {
            $url = $this->themeService->getThemeProperty('idp_provider_url');
        }

        $url = $url . '/realms/' . $themeService->getApplicationProperties('KEYCLOAK_REALM') . '/account/#/personal-info';
        return $this->redirect($url);
    }


    #[Route(path: '/login/keycloak_password', name: 'connect_keycloak_password')]
    public function password(ClientRegistry $clientRegistry, Request $request, ThemeService $themeService)
    {
        $url = $this->getParameter('KEYCLOAK_URL');
        if ($this->themeService->getThemeProperty('idp_provider')) {
            $url = $this->themeService->getThemeProperty('idp_provider_url');
        }
        $url = $url . '/realms/' . $themeService->getApplicationProperties('KEYCLOAK_REALM') . '/account/#/security/signingin';
        return $this->redirect($url);
    }
}
