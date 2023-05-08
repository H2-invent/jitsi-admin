<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Service\CreateHttpsUrl;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class HttpsAbsolutUrl extends AbstractExtension
{
    private $httpsUrl;
    private $paramterBag;

    public function __construct(CreateHttpsUrl $createHttpsUrl, ParameterBagInterface $parameterBag)
    {
        $this->httpsUrl = $createHttpsUrl;
        $this->paramterBag = $parameterBag;
    }

    public function getFunctions(): array
    {

        return [
            new TwigFunction('httpsAbolutUrl', [$this, 'httpsAbolutUrl']),
        ];
    }

    public function httpsAbolutUrl($url, ?Rooms $rooms = null)
    {
        return $this->httpsUrl->createHttpsUrl($url, $rooms);
    }
}
