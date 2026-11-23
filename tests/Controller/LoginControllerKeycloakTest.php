<?php

namespace App\Tests\Controller;

use App\Controller\LoginControllerKeycloak;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class LoginControllerKeycloakTest extends WebTestCase
{
    public function testIndexRedirectsToKeycloak(): void
    {
        $client = static::createClient();

        $client->request('GET', '/login');

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertStringContainsString('keycloak_login/check', urldecode($client->getResponse()->headers->get('Location')));
    }

    public function testRegisterRedirectsToKeycloakRegistration(): void
    {
        $client = static::createClient();

        $client->request('GET', '/register');

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertStringContainsString('protocol/openid-connect/registrations', $client->getResponse()->headers->get('Location'));
    }

    public function testEditRedirectsToKeycloakAccount(): void
    {
        $client = static::createClient();

        $client->request('GET', '/login/keycloak_edit');

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertStringContainsString('/account/#/personal-info', $client->getResponse()->headers->get('Location'));
    }

    public function testPasswordRedirectsToKeycloakAccount(): void
    {
        $client = static::createClient();

        $client->request('GET', '/login/keycloak_password');

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertStringContainsString('/account/#/security/signingin', $client->getResponse()->headers->get('Location'));
    }

    public function testCheckDoesNothing(): void
    {
        static::createClient();
        /** @var LoginControllerKeycloak $controller */
        $controller = self::getContainer()->get(LoginControllerKeycloak::class);

        $result = $controller->check(self::getContainer()->get(ClientRegistry::class), new Request());

        $this->assertNull($result);
    }
}
