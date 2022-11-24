<?php

namespace App\Controller;

use App\Helper\JitsiAdminController;
use App\Service\CreateHttpsUrl;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\Auth0Client;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUserProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

class LoginController extends JitsiAdminController
{
    /**
     * @Route("/login/auth0_login", name="login_auth0")
     */
    public function index(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry->getClient('auth0_main')->redirect(['user']);
    }

    /**
     * @Route("/login/auth0_login/check", name="connect_auth0_check")
     */
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

    /**
     * @Route("/room/logout_keycloak", name="logout_keycloak")
     */
    public function logout(ClientRegistry          $clientRegistry,
                           Request                 $request,
                           CreateHttpsUrl          $createHttpsUrl,
    )
    {
        $provider = new Keycloak([
            'authServerUrl' => $this->getParameter('KEYCLOAK_URL'),
            'realm' => $this->getParameter('KEYCLOAK_REALM'),
            'clientId' => $this->getParameter('KEYCLOAK_ID'),
            'clientSecret' => $this->getParameter('KEYCLOAK_SECRET'),
        ]);


        $redirectUri = $createHttpsUrl->createHttpsUrl('/login/logout');
        $url = $provider->getLogoutUrl(
            array(
                'id_token_hint' => $request->getSession()->get('id_token'),
                'post_logout_redirect_uri' => $redirectUri
            )
        );
        return $this->redirect($url);

    }
}
