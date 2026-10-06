<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Entity\User;
use App\UtilsHelper;

class Utils
{
    #[\Twig\Attribute\AsTwigFilter(name: 'addRepetiveCharacters')]
    public function addRepetiveCharacters(string $string, string $character, int $sequence): string
    {
        if ($sequence < 1) {
            $sequence = 1;
        }
        return chunk_split($string, $sequence, $character);
    }

    #[\Twig\Attribute\AsTwigFilter(name: 'json_decode')]
    public function json_decode(?string $string): mixed
    {
        $res = json_decode($string ?? '', true);
        return $res;
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'roomIsReadOnly')]
    public function roomIsReadOnly(Rooms $rooms, User $user): bool
    {
        return UtilsHelper::isRoomReadOnly($rooms, $user);
    }

    #[\Twig\Attribute\AsTwigFilter(name: 'colorFromString')]
    public function colorFromString(string $string): string
    {
        $code = dechex(crc32($string));
        $code = substr($code, 0, 6);
        return $code;
    }
}
