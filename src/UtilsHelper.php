<?php


namespace App;


class UtilsHelper
{
    public static function slugify($urlString)
    {
        $slug = preg_replace("/[^a-zA-Z0-9 ]/", "", strtolower($urlString));
        $slug = preg_replace("/[ ]/", "_", $slug);
        return $slug;
    }
    public static function slugifywithDot($urlString)
    {
        $slug = preg_replace("/[^a-zA-Z0-9. ]/", "", strtolower($urlString));
        $slug = preg_replace("/[ ]/", "_", $slug);
        return $slug;
    }
    public static function readable_random_string($length = 6)
    {
        $string = '';
        $vowels = array("a","e","i","o","u");
        $consonants = array(
            'b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm',
            'n', 'p', 'r', 's', 't', 'v', 'w', 'x', 'y', 'z'
        );

        $max = $length / 2;
        for ($i = 1; $i <= $max; $i++)
        {
            $string .= $consonants[rand(0,19)];
            $string .= $vowels[rand(0,4)];
        }

        return $string;
    }
}