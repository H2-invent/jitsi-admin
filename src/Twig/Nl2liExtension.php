<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Nl2liExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // If your filter generates SAFE HTML, you should add a third
            // parameter: ['is_safe' => ['html']]
            // Reference: https://twig.symfony.com/doc/2.x/advanced.html#automatic-escaping
            new TwigFilter('nl2li', [$this, 'nl2li'], ['is_safe' => ['html']]),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('nl2li', [$this, 'nl2li']),
        ];
    }

    public function nl2li($value)
    {
        // Check for http at beginning of string
        return '<li>' . str_replace("\n", "</li><li>", $value) . '</li>';
    }
}
