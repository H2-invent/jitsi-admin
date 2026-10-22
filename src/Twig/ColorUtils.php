<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use OzdemirBurak\Iris\Color\Hex;

class ColorUtils
{


    #[\Twig\Attribute\AsTwigFilter(name: 'color_lighten')]
    public function color_lighten(string $color, float $percent): string
    {
        try {
            $hex = new Hex(trim($color));
            return $hex->brighten($percent);
        } catch (\Exception) {
            return $color;
        }
    }

}
