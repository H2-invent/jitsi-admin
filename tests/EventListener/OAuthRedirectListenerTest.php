<?php

namespace App\Tests\EventListener;

use App\EventListener\OAuthRedirectListener;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

class OAuthRedirectListenerTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    private function request(string $path, ?string $wasLoggedIn = null, array $sessionValues = []): Request
    {
        $request = Request::create($path);
        if ($wasLoggedIn !== null) {
            $request->cookies->set('was_logged_in', $wasLoggedIn);
        }
        $session = new Session(new MockArraySessionStorage());
        foreach ($sessionValues as $key => $value) {
            $session->set($key, $value);
        }
        $request->setSession($session);

        return $request;
    }

    private function event(Request $request): RequestEvent
    {
        return new RequestEvent(self::$kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }

    private function listenerSession(): Session
    {
        return new Session(new MockArraySessionStorage());
    }

    private function router(): RouterInterface
    {
        return self::getContainer()->get(RouterInterface::class);
    }

    public function testIgnoresRequestsOutsideMyRoomStartPath(): void
    {
        $event = $this->event($this->request('/room/4711', '1'));

        (new OAuthRedirectListener($this->router(), $this->listenerSession()))->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testIgnoresRequestsWithoutLoginCookie(): void
    {
        foreach ([null, '0'] as $cookie) {
            $event = $this->event($this->request('/myRoom/start/abc', $cookie));

            (new OAuthRedirectListener($this->router(), $this->listenerSession()))->onKernelRequest($event);

            self::assertNull($event->getResponse());
        }
    }

    public function testIgnoresRequestsAlreadyOauthAuthenticated(): void
    {
        $event = $this->event($this->request('/myRoom/start/abc', '1', ['oauth_authenticated' => true]));

        (new OAuthRedirectListener($this->router(), $this->listenerSession()))->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testConnectKeycloakStartRouteIsMissingFromRouting(): void
    {
        $listenerSession = $this->listenerSession();
        $listener = new OAuthRedirectListener($this->router(), $listenerSession);
        $request = $this->request('/myRoom/start/abc', '1');
        $event = $this->event($request);

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('connect_keycloak_start');

        try {
            $listener->onKernelRequest($event);
        } finally {
            self::assertSame($request->getUri(), $listenerSession->get('target_path'));
        }
    }
}
