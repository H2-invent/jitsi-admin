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

    public function createHttpsUrl($url, ?Rooms $rooms = null)
    {
        if(str_contains($url, $this->baseUrl)){
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

    public function generateAbsolutUrl($baseUrl, $url = '')
    {
        $isStricktHttps = str_contains($this->baseUrl,'https://');
        $res = $baseUrl .$url;
        if ($isStricktHttps){
            $res = str_replace('http://', 'https://', $res);
        }
        return $res;
    }

}