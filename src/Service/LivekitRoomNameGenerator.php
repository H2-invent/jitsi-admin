<?php

namespace App\Service;

use App\Entity\Rooms;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

class LivekitRoomNameGenerator
{

    public function __construct(
        #[Autowire(param: 'laF_baseUrl')]
        private string $baseUrl,
        private readonly RequestStack $requestStack,
    )
    {
        $this->baseUrl = str_replace(['https://', 'http://'], '', $this->baseUrl);
    }

    public function getLiveKitName(Rooms $rooms): string
    {
        return "{$rooms->getUid()}@{$this->getExternalHost($rooms)}";
    }

    private function getExternalHost(Rooms $rooms): string
    {
        // The room record stores the public domain the meeting was created with,
        // prefer it so server-to-server callers (SIP dispatch rules via the API)
        // build the same room name as the browser join path does.
        $hostFromRoom = $rooms->getHostUrl() ? parse_url($rooms->getHostUrl(), PHP_URL_HOST) : null;
        if (\is_string($hostFromRoom) && !$this->isLocalhostOrPrivate($hostFromRoom)) {
            return $hostFromRoom;
        }

        $host = $this->requestStack->getMainRequest()?->getHost();
        if ($host === null || $this->isLocalhostOrPrivate($host)) {
            return $this->baseUrl;
        }

        return $host;
    }

    private function isLocalhostOrPrivate(string $host): bool
    {
        if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'])) {
            return true;
        }
        // if it's an ip, filter out all private and reserved ranges
        if (filter_var($host, FILTER_VALIDATE_IP) !== false && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        return false;
    }
}
