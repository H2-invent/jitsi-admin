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
    public function createHttpsUrl($url, ?Rooms $rooms = null): string
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
    private function generateAbsolutUrl($baseUrl, $url = ''): string
    {
        $isStricktHttps = str_contains($this->baseUrl, 'https://');
        $res = $baseUrl . $url;
        if ($isStricktHttps) {
            $res = str_replace('http://', 'https://', $res);
        }
        return $res;
    }

    /**
     * @param string $url
     * @return string
     */
    public function replaceSchemeOfAbsolutUrl($url): string
    {
        /** @var string $baseUrl */
        $baseUrl = $this->paramterBag->get('laF_baseUrl');
        $protokoll = parse_url($baseUrl);
        if (!$protokoll) {
            return $url;
        }
        try {
            $protokoll = $protokoll['scheme'];
            if ($protokoll) {
                return $this->replaceProtocol(url: $url, newProtocol: $protokoll);
            }
            return $url;
        }catch (\Exception $exception){
            return $url;
        }


    }

    /**
     * @param string $url
     * @param string $newProtocol
     * @return string
     */
    private function replaceProtocol($url, $newProtocol): string
    {
        $parsedUrl = parse_url($url);

        if ($parsedUrl && isset($parsedUrl['scheme'])) {
            $oldProtocol = $parsedUrl['scheme'];
            $newUrl = str_replace($oldProtocol, $newProtocol, $url);
            return $newUrl;
        }

        // Return original URL if no valid protocol was found
        return $url;
    }
}
