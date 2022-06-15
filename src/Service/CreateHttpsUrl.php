<?php

namespace App\Service;

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

    public function createHttpsUrl($url){
        if ($this->paramterBag->get('laF_baseUrl') !=''){
            return $this->paramterBag->get('laF_baseUrl').$url;
        }else{
            return $this->generateAbsolutUrl( $this->request->getCurrentRequest()->getSchemeAndHttpHost(),$url);
        }
    }

    public function generateAbsolutUrl($baseUrl, $url){
        return str_replace('http://','https://',$baseUrl).$url;
    }

}