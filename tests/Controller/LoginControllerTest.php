<?php

namespace App\Tests\Controller;

use App\Controller\LoginController;
use App\Repository\UserRepository;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\Auth0Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class LoginControllerTest extends WebTestCase
{
    public function testIndexRedirectsToAuth0(): void
    {
        static::createClient();
        /** @var LoginController $controller */
        $controller = self::getContainer()->get(LoginController::class);

        $client = $this->createMock(Auth0Client::class);
        $client->method('redirect')->willReturn(new RedirectResponse('/auth0-redirect'));
        $registry = $this->createMock(ClientRegistry::class);
        $registry->method('getClient')->with('auth0_main')->willReturn($client);

        $response = $controller->index($registry);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/auth0-redirect', $response->getTargetUrl());
    }

    public function testCheckRejectsUnknownClient(): void
    {
        static::createClient();
        /** @var LoginController $controller */
        $controller = self::getContainer()->get(LoginController::class);

        $this->expectException(\InvalidArgumentException::class);
        $controller->check(self::getContainer()->get(ClientRegistry::class), new Request());
    }

    public function testLogoutRedirectsToKeycloak(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/logout_keycloak');

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertStringContainsString('logout', $client->getResponse()->headers->get('Location'));
    }
}
