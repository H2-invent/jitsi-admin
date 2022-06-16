<?php

namespace App\Service;

use App\Entity\Rooms;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CreateHttpsUrl
{
    private $paramterBag;
    private $request;

    public function __construct(RequestStack $requestStack, ParameterBagInterface $parameterBag)
    {
        $this->paramterBag = $parameterBag;
        $this->request = $requestStack;
    }

    public function createHttpsUrl($url, ?Rooms $rooms = null)
    {
        if ($this->paramterBag->get('LAF_DEV_URL') !== '') {
            return $this->paramterBag->get('LAF_DEV_URL') . $url;
        } else {
            if ($rooms && $rooms->getHostUrl()) {
                return $this->generateAbsolutUrl($rooms->getHostUrl(), $url);
            }

            try {
                if ($this->request && $this->request->getCurrentRequest()) {
                    return $this->generateAbsolutUrl($this->request->getCurrentRequest()->getSchemeAndHttpHost(), $url);
                } else {
                    return $this->paramterBag->get('laF_baseUrl') . $url;
                }
            } catch (\Exception $exception) {
                return $this->paramterBag->get('laF_baseUrl') . $url;
            }
        }

    }

    public function generateAbsolutUrl($baseUrl, $url)
    {
        return str_replace('http://', 'https://', $baseUrl) . $url;
    }

}