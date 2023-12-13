<?php

namespace App\Service;

use App\Entity\Rooms;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CreateHttpsUrl
{
    private $paramterBag;
    private $request;
    private LoggerInterface $logger;

    private string $baseUrl;

    public function __construct(LoggerInterface $logger, RequestStack $requestStack, ParameterBagInterface $parameterBag)
    {
        $this->paramterBag = $parameterBag;
        $this->request = $requestStack;
        $this->logger = $logger;
        $this->baseUrl = $this->paramterBag->get('laF_baseUrl');
    }

    public function setParamterBag(ParameterBagInterface $paramterBag): void
    {
        $this->paramterBag = $paramterBag;
    }

    public function createHttpsUrl($url, ?Rooms $rooms = null)
    {
        if (str_contains($url, $this->baseUrl)) {
            return $this->generateAbsolutUrl($url);
        }


        if ($this->paramterBag->get('LAF_DEV_URL') !== '') {
            return $this->paramterBag->get('LAF_DEV_URL') . $url;
        } else {
            try {
                if ($rooms && $rooms->getHostUrl()) {
                    return $this->generateAbsolutUrl($rooms->getHostUrl(), $url);
                } elseif ($rooms && !$rooms->getHostUrl()) {
                    return $this->baseUrl . $url;
                } elseif ($this->request && $this->request->getCurrentRequest()) {
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

    private function generateAbsolutUrl($baseUrl, $url = '')
    {
        $isStricktHttps = str_contains($this->baseUrl, 'https://');
        $res = $baseUrl . $url;
        if ($isStricktHttps) {
            $res = str_replace('http://', 'https://', $res);
        }
        return $res;
    }

    public function replaceSchemeOfAbsolutUrl($url)
    {
        $protokoll = parse_url($this->paramterBag->get('laF_baseUrl'));
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

    private function replaceProtocol($url, $newProtocol)
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
