<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Service\LicenseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class Utils extends AbstractExtension
{
    private $licenseService;

    public function __construct(LicenseService $licenseService, TokenStorageInterface $tokenStorage, EntityManagerInterface $em)
    {
        $this->licenseService = $licenseService;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('addRepetiveCharacters', [$this, 'addRepetiveCharacters']),
            new TwigFilter('json_decode', [$this, 'json_decode']),
            new TwigFilter('colorFromString', [$this, 'colorFromString']),
        ];
    }

    public function addRepetiveCharacters(string $string, string $character, int $sequence): string
    {
        return chunk_split($string, $sequence, $character);
    }

    public function json_decode($string)
    {
        $res = json_decode($string ?? '', true);
        return $res;
    }

    public function colorFromString($string)
    {

        $code = dechex(crc32($string));
        $code = substr($code, 0, 6);
        return $code;
    }
}
