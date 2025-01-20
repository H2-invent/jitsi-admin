<?php

namespace App\Tests\Calendly;


use App\Entity\User;
use App\Service\calendly\CallendlyConnect;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CallendlyConnectTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private TranslatorInterface $translator;
    private ParameterBagInterface $parameterBag;
    private UrlGeneratorInterface $urlGenerator;
    private CallendlyConnect $calendlyConnect;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->calendlyConnect = new CallendlyConnect(
            $this->httpClient,
            $this->translator,
            $this->parameterBag,
            $this->urlGenerator
        );
    }

    public function testGetUserInfo(): void
    {
        $token = 'valid-token';
        $response = $this->createMock(ResponseInterface::class);

        $response->method('toArray')->willReturn(['key' => 'value']);
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', CallendlyConnect::BASE_URL . CallendlyConnect::INFO_ROUTE, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ],
            ])
            ->willReturn($response);

        $result = $this->calendlyConnect->getUserInfo($token);

        $this->assertSame(['key' => 'value'], $result);
    }

    public function testGetUserInfoWithClientException(): void
    {
        $this->httpClient
            ->method('request')
            ->willThrowException($this->createMock(ClientExceptionInterface::class));
        $this->translator
            ->method('trans')
            ->willReturn('translated error');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('translated error');

        $this->calendlyConnect->getUserInfo('invalid-token');
    }

    public function testRegisterWebhook(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getCalendlyOrgUri')->willReturn('org-uri');
        $user->method('getCalendlyUserUri')->willReturn('user-uri');
        $user->method('getCalendlyToken')->willReturn('token');

        $this->parameterBag
            ->method('get')
            ->with('laF_baseUrl')
            ->willReturn('http://localhost');
        $this->urlGenerator
            ->method('generate')
            ->with('app_calendly_webhook_api')
            ->willReturn('/webhook');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn(['success' => true]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $result = $this->calendlyConnect->registerWebhook($user);

        $this->assertSame(['success' => true], $result);
    }

    public function testCleanWebhooks(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getCalendlyToken')->willReturn('token');
        $webhookId = 'https://calendly.com/webhooks/12345';

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('DELETE', CallendlyConnect::BASE_URL . CallendlyConnect::WEBHOOK_ROUTE . '/12345');

        $result = $this->calendlyConnect->cleanWebhooks($user, $webhookId);

        $this->assertSame([], $result);
    }

    public function testGetWebhooks(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getCalendlyOrgUri')->willReturn('org-uri');
        $user->method('getCalendlyUserUri')->willReturn('user-uri');
        $user->method('getCalendlyToken')->willReturn('token');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn(['webhooks' => []]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $result = $this->calendlyConnect->getWebhooks($user);

        $this->assertSame(['webhooks' => []], $result);
    }
    public function testCleanWebhooksWithRedirectionException(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getCalendlyToken')->willReturn('token');
        $webhookId = 'https://calendly.com/webhooks/12345';

        $this->httpClient
            ->method('request')
            ->willThrowException($this->createMock(RedirectionExceptionInterface::class));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Weiterleitungsfehler bei der Calendly-API:');

        $this->calendlyConnect->cleanWebhooks($user, $webhookId);
    }
    public function testGetWebhooksWithTransportException(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getCalendlyToken')->willReturn('token');

        $this->httpClient
            ->method('request')
            ->willThrowException($this->createMock(TransportExceptionInterface::class));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Fehler beim Aufruf der Calendly-API:');

        $this->calendlyConnect->getWebhooks($user);
    }
    public function testRegisterWebhookWithClientException(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getCalendlyToken')->willReturn('invalid-token');

        $this->httpClient
            ->method('request')
            ->willThrowException($this->createMock(ClientExceptionInterface::class));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Ungültige Anfrage oder Token:');

        $this->calendlyConnect->registerWebhook($user);
    }

    public function testRegisterWebhookWithServerException(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getCalendlyToken')->willReturn('token');

        $this->httpClient
            ->method('request')
            ->willThrowException($this->createMock(ServerExceptionInterface::class));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Fehler beim Aufruf der Calendly-API:');

        $this->calendlyConnect->registerWebhook($user);
    }
    public function testGetUserInfoWithServerException(): void
    {
        $this->httpClient
            ->method('request')
            ->willThrowException($this->createMock(ServerExceptionInterface::class));
        $this->translator
            ->method('trans')
            ->willReturn('server error');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('server error');

        $this->calendlyConnect->getUserInfo('token');
    }

    public function testGetUserInfoWithTransportException(): void
    {
        $this->httpClient
            ->method('request')
            ->willThrowException($this->createMock(TransportExceptionInterface::class));
        $this->translator
            ->method('trans')
            ->willReturn('network error');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('network error');

        $this->calendlyConnect->getUserInfo('token');
    }

    public function testGetUserInfoWithRedirectionException(): void
    {
        $this->httpClient
            ->method('request')
            ->willThrowException($this->createMock(RedirectionExceptionInterface::class));
        $this->translator
            ->method('trans')
            ->willReturn('redirect error');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('redirect error');

        $this->calendlyConnect->getUserInfo('token');
    }
}