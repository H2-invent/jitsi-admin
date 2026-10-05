<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\User;
use App\Service\FormatName;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NameWithFormat
{
    public function __construct(private readonly FormatName $formateName)
    {
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'nameOfUserwithFormat')]
    public function nameOfUserwithFormat(User $user, string $string): string
    {
        return $this->formateName->formatName($string, $user);
    }
}
