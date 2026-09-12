<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\User;
use App\Service\FormatName;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NameWithFormat extends AbstractExtension
{
    private $formateName;
    public function __construct(FormatName $formatName)
    {
        $this->formateName = $formatName;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('nameOfUserwithFormat', [$this, 'nameOfUserwithFormat']),
        ];
    }

    public function nameOfUserwithFormat(User $user, $string)
    {
        return $this->formateName->formatName($string, $user);
    }
}
