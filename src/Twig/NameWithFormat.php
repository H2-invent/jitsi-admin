<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\MyUser;
use App\Entity\User;
use App\Service\FormatName;
use App\Service\MessageService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use function GuzzleHttp\Psr7\str;

class NameWithFormat extends AbstractExtension
{
    private $formateName;
    public function __construct(FormatName $formatName)
    {
        $this->formateName = $formatName;
    }

    public function getFunctions()
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
