<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Entity\User;
use App\Service\LicenseService;
use App\UtilsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Utils extends AbstractExtension
{
    private $licenseService;

    public function __construct(LicenseService $licenseService)
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

    public function getFunctions(): array
    {
        return [
            new TwigFunction('roomIsReadOnly', [$this, 'roomIsReadOnly'])
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

    public function roomIsReadOnly(Rooms $rooms, User $user)
    {
        return UtilsHelper::isRoomReadOnly($rooms, $user);
    }

    public function colorFromString($string)
    {

        $code = dechex(crc32($string));
        $code = substr($code, 0, 6);
        return $code;
    }
}
