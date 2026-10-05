<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Nl2liExtension
{
    #[\Twig\Attribute\AsTwigFilter(name: 'nl2li', isSafe: ['html'])]
    #[\Twig\Attribute\AsTwigFunction(name: 'nl2li')]
    public function nl2li(string $value): string
    {
        // Check for http at beginning of string
        return '<li>' . str_replace("\n", "</li><li>", $value) . '</li>';
    }
}
