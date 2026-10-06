<?php

namespace App\Service;

use App\Entity\Rooms;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CreateHttpsUrl
{
    private readonly string $baseUrl;

    public function __construct(private readonly LoggerInterface $logger, private readonly RequestStack $request, private ParameterBagInterface $paramterBag)
    {
        /** @var string $baseUrl */
        $baseUrl       = $this->paramterBag->get('laF_baseUrl');
        $this->baseUrl = $baseUrl;
    }

    public function setParamterBag(ParameterBagInterface $paramterBag): void
    {
        $this->paramterBag = $paramterBag;
    }

    public function createHttpsUrl(string $url, ?Rooms $rooms = null): string
    {
        if (str_contains($url, $this->baseUrl)) {
            return $this->generateAbsolutUrl($url);
        }

        $lafDevUrl = (string)$this->paramterBag->get('LAF_DEV_URL');
        if ($lafDevUrl !== '') {
            return $lafDevUrl . $url;
        }

        try {
            if ($rooms && $rooms->getHostUrl()) {
                return $this->generateAbsolutUrl($rooms->getHostUrl(), $url);
            } elseif ($rooms && !$rooms->getHostUrl()) {
                return $this->baseUrl . $url;
            } elseif ($this->request->getCurrentRequest()) {
                return $this->generateAbsolutUrl($this->request->getCurrentRequest()->getSchemeAndHttpHost(), $url);
            } else {
                return $this->baseUrl . $url;
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return $this->baseUrl . $url;
        }
    }

    public function generateAbsolutUrl(string $baseUrl, string $url = ''): string
    {
        $isStrictHttps = str_contains($this->baseUrl, 'https://');
        $res           = $baseUrl . $url;
        if (!$isStrictHttps) {
            return $res;
        }

        return str_replace('http://', 'https://', $res);
    }

    public function replaceSchemeOfAbsolutUrl(string $url): string
    {
        $baseUrl = $this->paramterBag->get('laF_baseUrl');
        $scheme  = parse_url($baseUrl, PHP_URL_SCHEME);
        if (!$scheme) {
            return $url;
        }

        return $this->replaceProtocol($url, $scheme);
    }

    private function replaceProtocol(string $url, string $newProtocol): string
    {
        $oldProtocol = parse_url($url, PHP_URL_SCHEME);
        if (!$oldProtocol) {
            return $url;
        }

        return str_replace($oldProtocol, $newProtocol, $url);
    }
}