<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Service\MessageService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function GuzzleHttp\Psr7\str;

class ServerStarView extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('showAverageStar', [$this, 'showAverageStar']),
        ];
    }

    public function showAverageStar(\App\Entity\Server $server)
    {
        $star = 0;
        $count = 0;
        foreach ($server->getStars() as $data) {
            $star += $data->getStar();
            $count++;
        }
        if ($count > 0) {
            $star = $star / $count;
        }

        return $star;
    }
}
