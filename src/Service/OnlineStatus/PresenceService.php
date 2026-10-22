<?php

namespace App\Service\OnlineStatus;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PresenceService
{
    // Presence is only a hint for an interactive action; keep the lookup short so a slow
    // websocket service cannot delay the ad-hoc start for long.
    private const PRESENCE_TIMEOUT = 0.5;

    public function __construct(
        private HttpClientInterface $httpClient,
        private ParameterBagInterface $parameterBag,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Returns true when the user has an active websocket connection (online, away or in a meeting).
     * Returns false when the user is offline.
     * Returns null when the presence of the user cannot be determined (service unavailable).
     */
    public function isUserOnline(User $user): ?bool
    {
        $status = $this->getStatusForUid($user->getUid());
        if ($status === null) {
            return null;
        }

        return $status !== 'offline';
    }

    /**
     * Queries the websocket service for a single uid.
     * Returns the status ("online", "away", "inMeeting" or "offline"), or null when the presence
     * service is unreachable/unknown so the caller can fall back to another source.
     */
    public function getStatusForUid(?string $uid): ?string
    {
        if (!$uid) {
            return null;
        }

        $baseUrl = $this->getInternalBaseUrl();
        if (!$baseUrl) {
            return null;
        }

        try {
            $response = $this->httpClient->request(
                'GET',
                $baseUrl . '/presence/' . rawurlencode($uid),
                [
                    'headers' => ['Authorization' => 'Bearer ' . $this->parameterBag->get('WEBSOCKET_SECRET')],
                    'timeout' => self::PRESENCE_TIMEOUT,
                ]
            );

            if ($response->getStatusCode() !== 200) {
                $this->logger->warning('Websocket presence lookup returned status {status}', ['status' => $response->getStatusCode()]);
                return null;
            }

            $data = $response->toArray(false);

            return isset($data['status']) && is_string($data['status']) ? $data['status'] : null;
        } catch (\Throwable $exception) {
            $this->logger->warning('Websocket presence lookup failed: {message}', ['message' => $exception->getMessage()]);
            return null;
        }
    }

    /**
     * The presence endpoint lives on the same host/port as the Mercure hub, so when no explicit
     * WEBSOCKET_INTERNAL_URL is configured we derive it from MERCURE_URL by stripping the hub path.
     */
    private function getInternalBaseUrl(): ?string
    {
        $url = trim((string)$this->parameterBag->get('WEBSOCKET_INTERNAL_URL'));
        if ($url === '') {
            $url = trim((string)$this->parameterBag->get('MERCURE_URL'));
        }
        if ($url === '') {
            return null;
        }

        $url = preg_replace('#/\.well-known/mercure/?$#', '', rtrim($url, '/'));

        return $url !== '' ? rtrim($url, '/') : null;
    }
}
