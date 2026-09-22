<?php

namespace App\Service;

use App\Entity\Rooms;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CreateHttpsUrl
{
    private ParameterBagInterface $paramterBag;
    private RequestStack $request;
    private LoggerInterface $logger;
    private string $baseUrl;

    public function __construct(LoggerInterface $logger, RequestStack $requestStack, ParameterBagInterface $parameterBag)
    {
        $this->paramterBag = $parameterBag;
        $this->request = $requestStack;
        $this->logger = $logger;
        /** @var string $baseUrl */
        $baseUrl = $this->paramterBag->get('laF_baseUrl');
        $this->baseUrl = $baseUrl;
    }

    public function setParamterBag(ParameterBagInterface $paramterBag): void
    {
        $this->paramterBag = $paramterBag;
    }

    /**
     * @param string $url
     * @param Rooms|null $rooms
     * @return string
     */
    public function createHttpsUrl(string $url, ?Rooms $rooms = null): string
    {
        if (str_contains($url, $this->baseUrl)) {
            return $this->generateAbsolutUrl($url);
        }

        if ($this->paramterBag->get('LAF_DEV_URL') !== '') {
            /** @var string $lafDevUrl */
            $lafDevUrl = $this->paramterBag->get('LAF_DEV_URL');
            return $lafDevUrl . $url;
        } else {
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
    }

    /**
     * @param string $baseUrl
     * @param string $url
     * @return string
     */
    public function generateAbsolutUrl(string $baseUrl, string $url = ''): string
    {
        $isStrictHttps = str_contains($this->baseUrl, 'https://');
        $res = $baseUrl . $url;
        if (!$isStrictHttps) {
            return $res;
        }

        return str_replace('http://', 'https://', $res);
    }

    /**
     * @param string $url
     * @return string
     */
    public function replaceSchemeOfAbsolutUrl(string $url): string
    {
        $baseUrl = $this->paramterBag->get('laF_baseUrl');
        $scheme  = parse_url($baseUrl, PHP_URL_SCHEME);
        if (!$scheme) {
            return $url;
        }

        return $this->replaceProtocol($url, $scheme);
    }

    /**
     * @param string $url
     * @param string $newProtocol
     * @return string
     */
    private function replaceProtocol(string $url, string $newProtocol): string
    {
        $oldProtocol = parse_url($url, PHP_URL_SCHEME);
        if (!$oldProtocol) {
            return $url;
        }

        return str_replace($oldProtocol, $newProtocol, $url);
    }
}
