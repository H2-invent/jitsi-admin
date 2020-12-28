<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LoginControllerKeycloak extends AbstractController
{
    /**
     * @Route("/login", name="login_keycloak")
     */
    public function index(ClientRegistry $clientRegistry): Response
    {
      return $clientRegistry->getClient('keycloak_main')->redirect(['email']);
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
        $url = $this->getParameter('KEYCLOAK_URL').'/realms/'.$this->getParameter('KEYCLOAK_REALM').'/account';
        return $this->redirect($url);
    }
}
