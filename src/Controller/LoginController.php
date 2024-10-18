<?php

namespace App\Controller;

use App\Helper\JitsiAdminController;
use App\Service\CreateHttpsUrl;
use App\Service\ThemeService;
use Doctrine\Persistence\ManagerRegistry;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\Auth0Client;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Log\LoggerInterface;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoginController extends JitsiAdminController
{
    public function __construct(
        ManagerRegistry        $managerRegistry,
        TranslatorInterface    $translator,
        LoggerInterface        $logger,
        ParameterBagInterface  $parameterBag,
        private CreateHttpsUrl $createHttpsUrl)
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
    }

    #[Route(path: '/login/auth0_login', name: 'login_auth0')]
    public function index(
        ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry->getClient('auth0_main')->redirect(['user']);
    }

    #[Route(path: '/login/auth0_login/check', name: 'connect_auth0_check')]
    public function check(ClientRegistry $clientRegistry, Request $request)
    {
        // ** if you want to *authenticate* the user, then
        // leave this method blank and create a Guard authenticator
        // (read below)

        /** @var Auth0Client $client */
        $client = $clientRegistry->getClient('auth0_main');

        try {
            $user = $client->fetchUser();

            // do something with all this new power!
            // e.g. $name = $user->getFirstName();
            die;
            // ...
        } catch (IdentityProviderException $e) {
            // something went wrong!
            // probably you should return the reason to the user
            die;
        }
    }

    #[Route(path: '/room/logout_keycloak', name: 'logout_keycloak')]
    public function logout(
        ClientRegistry $clientRegistry,
        Request        $request,
        CreateHttpsUrl $createHttpsUrl,
        ThemeService   $themeService,
    )
    {
        $provider = new Keycloak(
            [
                'authServerUrl' => $this->getParameter('KEYCLOAK_URL'),
                'realm' => $this->getParameter('KEYCLOAK_REALM'),
                'clientId' => $this->getParameter('KEYCLOAK_ID'),
                'clientSecret' => $this->getParameter('KEYCLOAK_SECRET'),
            ]
        );

        $options = ['post_logout_redirect_uri' => $this->createHttpsUrl->replaceSchemeOfAbsolutUrl($this->generateUrl('app_logout', [], UrlGenerator::ABSOLUTE_URL))];


        $options['id_token_hint'] = $request->getSession()->get('id_token');

        if ($themeService->getApplicationProperties('idp_provider')) {
            $options['kc_idp_hint'] = $themeService->getApplicationProperties('idp_provider');
        }


        $url = $provider->getLogoutUrl(
            $options
        );
        return $this->redirect($url);
    }
}
