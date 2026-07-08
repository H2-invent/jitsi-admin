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
        return "{$rooms->getUid()}@{$this->getExternalHost()}";
    }

    private function getExternalHost(): string
    {
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
