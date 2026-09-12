<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ServerStarView extends AbstractExtension
{
    public function getFunctions(): array
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
