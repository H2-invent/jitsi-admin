<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Entity\User;
use App\UtilsHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Utils extends AbstractExtension
{
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
        if ($sequence < 1) {
            $sequence = 1;
        }
        return chunk_split($string, $sequence, $character);
    }

    /**
     * @param string|null $string
     * @return mixed
     */
    public function json_decode(?string $string): mixed
    {
        $res = json_decode($string ?? '', true);
        return $res;
    }

    public function roomIsReadOnly(Rooms $rooms, User $user): bool
    {
        return UtilsHelper::isRoomReadOnly($rooms, $user);
    }

    /**
     * @param string $string
     */
    public function colorFromString(string $string): string
    {

        $code = dechex(crc32($string));
        $code = substr($code, 0, 6);
        return $code;
    }
}
