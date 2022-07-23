<?php

namespace App\Service\Jigasi;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Service\LicenseService;
use App\Service\ThemeService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function _PHPStan_9a6ded56a\RingCentral\Psr7\str;

class JigasiService
{
    public function __construct(
        private HttpClientInterface   $client,
        private LoggerInterface       $logger,
        private LicenseService        $licenseService,
        private AdapterInterface      $cache,
        private ParameterBagInterface $parameterBag,
        private KernelInterface       $kernel)
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

            if ($this->kernel->getEnvironment() === 'test') {
                $this->cache->delete('jigasi_phonenumber_' . $server->getId());
            }

            $res = $this->cache->get('jigasi_phonenumber_' . $server->getId(), function (ItemInterface $item) use ($server) {
                $item->expiresAfter(3600);
                try {
                    $response = $this->client->request(
                        'GET',
                        $server->getJigasiNumberUrl()
                    );
                    $responseArr = json_decode($this->sanitizeResponse($response->getContent()), true);
                    $numbers = $responseArr['numbers'];
                } catch (\Exception $exception) {
                    $this->logger->info($response->getContent());
                    $this->logger->error($exception->getMessage());
                    echo $exception->getMessage();
                    return null;
                }
                return $numbers;
            });
            return $res;
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

            $sipPin = $this->cache->get('jigasi_pin_' . $rooms->getUid(), function (ItemInterface $item) use ($server, $rooms) {
                $item->expiresAfter(3600);
                try {
                    $pin = $this->pingJigasi($rooms);
                } catch (\Exception $exception) {
                    $this->logger->error($exception->getMessage());
                    return null;
                }
                return $pin;
            });
            return $sipPin;
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

    public function pingJigasi(?Rooms $rooms):?string
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
                $this->logger->info($response->getContent());
                $this->logger->error($exception->getMessage());
                return null;
            }
        }
        return null;
    }
}