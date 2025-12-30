<?php

namespace App\EventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OAuthRedirectListener
{

    public function __construct(
        private RouterInterface $router,
        private SessionInterface $session
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Nur für bestimmte Pfade aktiv (z. B. beginnt mit /myRoom/start/)
        $path = $request->getPathInfo();
        if (!str_starts_with($path, '/myRoom/start/')) {
            return;
        }

        $cookie = $request->cookies->get('was_logged_in');

        if ($cookie === '1' && !$request->getSession()->has('oauth_authenticated')) {
            // Aktuelle URL merken für Rückleitung
            $this->session->set('target_path', $request->getUri());

            // Weiterleitung zu Keycloak Login
            $redirectUrl = $this->router->generate('connect_keycloak_start');

            $event->setResponse(new RedirectResponse($redirectUrl));
        }
    }

}