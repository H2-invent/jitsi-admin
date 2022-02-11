<?php


namespace App;


class UtilsHelper
{
    public static function slugify($urlString)
    {
        $slug = preg_replace("/[^a-zA-Z0-9 ]/", "", $urlString);
        $slug = preg_replace("/[ ]/", "_", $slug);
        return $slug;
    }


}