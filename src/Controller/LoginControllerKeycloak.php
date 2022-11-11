<?php

namespace App\Controller;

use App\Helper\JitsiAdminController;
use App\Service\CreateHttpsUrl;
use App\Service\ThemeService;
use Doctrine\Persistence\ManagerRegistry;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoginControllerKeycloak extends JitsiAdminController
{
    private ThemeService $themeService;

    public function __construct(ThemeService $themeService, ManagerRegistry $managerRegistry, TranslatorInterface $translator, LoggerInterface $logger, ParameterBagInterface $parameterBag)
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
        $this->themeService = $themeService;
    }

    /**
     * @Route("/login", name="login_keycloak")
     */
    public function index(ClientRegistry $clientRegistry): Response
    {
        $options = array();

        if ($this->themeService->getThemeProperty('idp_provider')) {
            $options['kc_idp_hint'] = $this->themeService->getThemeProperty('idp_provider');
        }
        $res = $clientRegistry->getClient('keycloak_main')->redirect(['email'], $options);
        return  $res;

    }


    /**
     * @Route("/register", name="register_keycloak")
     */
    public function register(ClientRegistry $clientRegistry, CreateHttpsUrl $createHttpsUrl): Response
    {
        $url = $this->getParameter('KEYCLOAK_URL') . '/realms/' . $this->getParameter('KEYCLOAK_REALM') . '/protocol/openid-connect/registrations?client_id=' .
            $this->getParameter('KEYCLOAK_ID') .
            '&response_type=code&scope=openid email&redirect_uri=' . $createHttpsUrl->createHttpsUrl($this->generateUrl('connect_keycloak_check', array())) . '&kc_locale=de';
        return $this->redirect($url);
    }

    /**
     * @Route("/login/keycloak_login/check", name="connect_keycloak_check")
     */
    public function check(ClientRegistry $clientRegistry, Request $request)
    {

    }

    /**
     * @Route("/login/keycloak_edit", name="connect_keycloak_edit")
     */
    public function edit(ClientRegistry $clientRegistry, Request $request)
    {
        $url = $this->getParameter('KEYCLOAK_URL') . '/realms/' . $this->getParameter('KEYCLOAK_REALM') . '/account';
        return $this->redirect($url);
    }

    /**
     * @Route("/login/keycloak_password", name="connect_keycloak_password")
     */
    public function password(ClientRegistry $clientRegistry, Request $request)
    {
        $url = $this->getParameter('KEYCLOAK_URL') . '/realms/' . $this->getParameter('KEYCLOAK_REALM') . '/account/password';
        return $this->redirect($url);
    }
}
