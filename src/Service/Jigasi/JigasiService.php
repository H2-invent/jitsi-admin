<?php

namespace App\Service\Jigasi;

use App\Entity\Rooms;
use App\Service\LicenseService;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class JigasiService
{
    public function __construct(
        private HttpClientInterface    $client,
        private LoggerInterface        $logger,
        private LicenseService         $licenseService,
        private CacheItemPoolInterface $cache,
        private KernelInterface        $kernel
    )
    {
    }

    public function setClient(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function getNumber(?Rooms $rooms)
    {
        if (!$rooms) {
            return null;
        }
        $server = $rooms->getServer();
        if ($server && $this->licenseService->verify($server) && $server->getJigasiNumberUrl()) {
            try {
                $responseArr = json_decode($server->getJigasiNumberUrl(), true);
                $numbers = null;

                if (isset($responseArr['numbers'])) {
                    $numbers = $responseArr['numbers'];
                }
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage());
                return null;
            }
            return $numbers;
        }
        return null;
    }

    public function getRoomPin(?Rooms $rooms)
    {
        if (!$rooms) {
            return null;
        }
        $server = $rooms->getServer();
        if ($server && $this->licenseService->verify($server) && $server->getJigasiApiUrl()) {
            if ($this->kernel->getEnvironment() === 'test') {
                $this->cache->delete('jigasi_pin_' . $rooms->getUid());
            }

            $sipPin = $this->cache->get(
                'jigasi_pin_' . $rooms->getUid(),
                function (ItemInterface $item) use ($server, $rooms) {
                    $item->expiresAfter(3600);
                    try {
                        $pin = $this->pingJigasi($rooms);
                    } catch (\Exception $exception) {
                        $item->expiresAfter(1);
                        return null;
                    }
                    return $pin;
                }
            );
            return $sipPin;
        }
        return null;
    }

    public function pingJigasi(?Rooms $rooms): ?string
    {
        if (!$rooms) {
            return null;
        }
        $server = $rooms->getServer();
        if ($server && $this->licenseService->verify($server) && $server->getJigasiApiUrl()) {
            try {
                $response = $this->client->request(
                    'GET',
                    $server->getJigasiApiUrl() . '?conference=' . $rooms->getUid() . '@' . $server->getJigasiProsodyDomain() . '&url=https://' . $server->getUrl() . '/' . $rooms->getUid()
                );
                $responseArr = json_decode($response->getContent(), true);
                $pin = $responseArr['id'];
                return $pin;
            } catch (\Exception $exception) {
                if ($response->getStatusCode() === 200) {
                    $this->logger->info(printf("%s: %s", 'Receive HTML', $response->getContent()));
                }
                $this->logger->error($exception->getMessage());
                return null;
            }
        }
        return null;
    }


    public function sanitizeResponse(string $input): ?string
    {
        $stringSanitize = $input;
        $stringSanitize = preg_replace('/^(.*?)\{/', '{', $stringSanitize);
        $stringSanitize = preg_replace('/([^}]*)$/', '', $stringSanitize);
        $stringSanitize = trim(preg_replace('/\s\s+/m', ' ', $stringSanitize));
        return $stringSanitize;
    }
}
