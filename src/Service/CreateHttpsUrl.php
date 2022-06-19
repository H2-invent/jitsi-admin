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

    public function __construct(LoggerInterface $logger, RequestStack $requestStack, ParameterBagInterface $parameterBag)
    {
        $this->paramterBag = $parameterBag;
        $this->request = $requestStack;
        $this->logger = $logger;
    }

    public function createHttpsUrl($url, ?Rooms $rooms = null)
    {
        if ($this->paramterBag->get('LAF_DEV_URL') !== '') {
            return $this->paramterBag->get('LAF_DEV_URL') . $url;
        } else {
            try {
                if ($rooms && $rooms->getHostUrl()) {
                    return $this->generateAbsolutUrl($rooms->getHostUrl(), $url);
                } elseif ($rooms && !$rooms->getHostUrl()) {
                    return $this->paramterBag->get('laF_baseUrl') . $url;
                } elseif ($this->request && $this->request->getCurrentRequest()) {
                    return $this->generateAbsolutUrl($this->request->getCurrentRequest()->getSchemeAndHttpHost(), $url);
                } else {
                    return $this->paramterBag->get('laF_baseUrl') . $url;
                }
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage());
                return $this->paramterBag->get('laF_baseUrl') . $url;
            }
        }

    }

    public function generateAbsolutUrl($baseUrl, $url)
    {
        return str_replace('http://', 'https://', $baseUrl) . $url;
    }

}