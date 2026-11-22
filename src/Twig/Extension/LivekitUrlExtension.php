<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\LivekitUrlRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class LivekitUrlExtension extends AbstractExtension
{
    public function __construct()
    {
    }


    public function getFunctions(): array
    {
        return [
            new TwigFunction('getLiveKitName', [LivekitUrlRuntime::class, 'getLiveKitName']),
        ];
    }
}
