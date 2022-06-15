<?php

namespace App\Controller;

use App\Helper\JitsiAdminController;
use App\Service\CreateHttpsUrl;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LoginControllerKeycloak extends JitsiAdminController
{
    /**
     * @Route("/login", name="login_keycloak")
     */
    public function index(ClientRegistry $clientRegistry): Response
    {
      return $clientRegistry->getClient('keycloak_main')->redirect(['email']);
    }

    /**
     * @Route("/register", name="register_keycloak")
     */
    public function register(ClientRegistry $clientRegistry, CreateHttpsUrl $createHttpsUrl): Response
    {
        $url = $this->getParameter('KEYCLOAK_URL').'/realms/'.$this->getParameter('KEYCLOAK_REALM').'/protocol/openid-connect/registrations?client_id='.
            $this->getParameter('KEYCLOAK_ID').
            '&response_type=code&scope=openid email&redirect_uri='.$createHttpsUrl->createHttpsUrl($this->generateUrl('connect_keycloak_check',array())).'&kc_locale=de';
        return $this->redirect($url);
    }


    public function check(ClientRegistry $clientRegistry, Request $request)
    {

    }

    /**
     * @Route("/login/keycloak_edit", name="connect_keycloak_edit")
     */
    public function edit(ClientRegistry $clientRegistry, Request $request)
    {
        $url = $this->getParameter('KEYCLOAK_URL').'/realms/'.$this->getParameter('KEYCLOAK_REALM').'/account';
        return $this->redirect($url);
    }
    /**
     * @Route("/login/keycloak_password", name="connect_keycloak_password")
     */
    public function password(ClientRegistry $clientRegistry, Request $request)
    {
        $url = $this->getParameter('KEYCLOAK_URL').'/realms/'.$this->getParameter('KEYCLOAK_REALM').'/account/password';
        return $this->redirect($url);
    }
}
