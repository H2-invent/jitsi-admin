<?php

namespace App\Service\calendly;

use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use function OpenTelemetry\Instrumentation\hook;

class CallendlyConnect
{
    const BASE_URL = 'https://api.calendly.com/';
    const INFO_ROUTE = 'users/me';
    const WEBHOOK_ROUTE = 'webhook_subscriptions';

    public function __construct(
        private HttpClientInterface $httpClient,
        private TranslatorInterface $translator,
        private ParameterBagInterface $parameterBag,
        private UrlGeneratorInterface $urlGenerator,
    )
    {
    }

    public function getUserInfo(string $token): array
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                self::BASE_URL . self::INFO_ROUTE,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Accept' => 'application/json',
                    ],
                ]
            );

            // Konvertiert die Antwort in ein Array
            $data = $response->toArray();

            // Gibt relevante Informationen zurück
            return $data;
        } catch (ClientExceptionInterface $e) {
            // Fehler bei Client-Request (z. B. 400 oder 401)
            throw new \RuntimeException($this->translator->trans('calendly.connect.wrongToken'));
        } catch (ServerExceptionInterface|TransportExceptionInterface $e) {
            // Fehler bei der Serverantwort oder Netzwerkproblemen
            throw new \RuntimeException($this->translator->trans('calendly.connect.netWorkError'));
        } catch (RedirectionExceptionInterface $e) {
            // Fehler bei Weiterleitungen
            throw new \RuntimeException($this->translator->trans('calendly.connect.redirectError'));
        }
    }

    public function registerWebhook(
        User $user,
    ): array {
        try {
            // Bereite die Anfrage-Payload vor
            $payload = [
                'url' => str_replace('localhost','h2-invent.com',$this->parameterBag->get('laF_baseUrl')).$this->urlGenerator->generate('app_calendly_webhook_api'),
                'events' => ['invitee.created','invitee.canceled','invitee.canceled'],
                'organization' => $user->getCalendlyOrgUri(),
                'user' => $user->getCalendlyUserUri(),
                'scope' => 'user',
            ];

            // Sende die Anfrage
            $response = $this->httpClient->request(
                'POST',
                self::BASE_URL . self::WEBHOOK_ROUTE,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $user->getCalendlyToken(),
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $payload,
                ]
            );

            // Konvertiere die Antwort in ein Array und gebe sie zurück
            return $response->toArray();
        } catch (ClientExceptionInterface $e) {
            // Fehler bei der Anfrage (z. B. 400 oder 401)
            throw new \RuntimeException('Ungültige Anfrage oder Token: ' . $e->getMessage());
        } catch (ServerExceptionInterface | TransportExceptionInterface $e) {
            // Fehler bei der Serverantwort oder Netzwerkproblemen
            throw new \RuntimeException('Fehler beim Aufruf der Calendly-API: ' . $e->getMessage());
        } catch (RedirectionExceptionInterface $e) {
            // Fehler bei Weiterleitungen
            throw new \RuntimeException('Weiterleitungsfehler bei der Calendly-API: ' . $e->getMessage());
        }
    }
    public function getWebhooks(
        User $user,
    ): array {
        try {
            // Bereite die Anfrage-Payload vor
            $payload = [
                'user'=>$user->getCalendlyUserUri(),
                'organization' => $user->getCalendlyOrgUri(),
                'scope' => 'user',
            ];


            // Sende die Anfrage
            $response = $this->httpClient->request(
                'GET',
                self::BASE_URL . self::WEBHOOK_ROUTE,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $user->getCalendlyToken(),
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $payload,
                ]
            );

            // Konvertiere die Antwort in ein Array und gebe sie zurück
            $hooks =  $response->toArray();


            return $hooks;
        } catch (ClientExceptionInterface $e) {
            // Fehler bei der Anfrage (z. B. 400 oder 401)
            throw new \RuntimeException('Ungültige Anfrage oder Token: ' . $e->getMessage());
        } catch (ServerExceptionInterface | TransportExceptionInterface $e) {
            // Fehler bei der Serverantwort oder Netzwerkproblemen
            throw new \RuntimeException('Fehler beim Aufruf der Calendly-API: ' . $e->getMessage());
        } catch (RedirectionExceptionInterface $e) {
            // Fehler bei Weiterleitungen
            throw new \RuntimeException('Weiterleitungsfehler bei der Calendly-API: ' . $e->getMessage());
        }
    }

    public function cleanWebhooks(
        User $user,
        $webhookId
    ): array {
        try {
            $response = $this->httpClient->request(
                'DELETE',
                self::BASE_URL . self::WEBHOOK_ROUTE.'/'.array_reverse(explode('/', $webhookId))[0],
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $user->getCalendlyToken(),
                        'Content-Type' => 'application/json',
                    ],
                ]
            );

            // Konvertiere die Antwort in ein Array und gebe sie zurück
            return [];
        } catch (ClientExceptionInterface $e) {
            // Fehler bei der Anfrage (z. B. 400 oder 401)
            throw new \RuntimeException('Ungültige Anfrage oder Token: ' . $e->getMessage());
        } catch (ServerExceptionInterface | TransportExceptionInterface $e) {
            // Fehler bei der Serverantwort oder Netzwerkproblemen
            throw new \RuntimeException('Fehler beim Aufruf der Calendly-API: ' . $e->getMessage());
        } catch (RedirectionExceptionInterface $e) {
            // Fehler bei Weiterleitungen
            throw new \RuntimeException('Weiterleitungsfehler bei der Calendly-API: ' . $e->getMessage());
        }
    }

}