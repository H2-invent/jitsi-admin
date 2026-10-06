<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Service\CreateHttpsUrl;

class HttpsAbsolutUrl
{
    public function __construct(private readonly CreateHttpsUrl $httpsUrl)
    {
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'httpsAbolutUrl')]
    public function httpsAbolutUrl(string $url, ?Rooms $rooms = null): string
    {
        return $this->httpsUrl->createHttpsUrl($url, $rooms);
    }
}
