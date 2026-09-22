<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Service\CreateHttpsUrl;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class HttpsAbsolutUrl extends AbstractExtension
{
    private CreateHttpsUrl $httpsUrl;

    public function __construct(CreateHttpsUrl $createHttpsUrl)
    {
        $this->httpsUrl = $createHttpsUrl;
    }

    public function getFunctions(): array
    {

        return [
            new TwigFunction('httpsAbolutUrl', [$this, 'httpsAbolutUrl']),
        ];
    }

    /**
     * @param string $url
     * @param Rooms|null $rooms
     * @return string
     */
    public function httpsAbolutUrl(string $url, ?Rooms $rooms = null): string
    {
        return $this->httpsUrl->createHttpsUrl($url, $rooms);
    }
}
