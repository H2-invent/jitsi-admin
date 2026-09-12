<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use OzdemirBurak\Iris\Color\Hex;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ColorUtils extends AbstractExtension
{


    public function getFilters(): array
    {

        return [
            new TwigFilter('color_lighten', [$this, 'color_lighten']),

        ];
    }
    public function color_lighten($color,$percent):string{
        try {
            $hex = new Hex(trim($color));
            return $hex->brighten($percent);
        }catch (\Exception $exception){
            return $color;
        }

    }

}
