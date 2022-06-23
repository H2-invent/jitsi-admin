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

}