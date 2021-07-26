<?php


namespace App;


class UtilsHelper
{
    public static function slugify($urlString)
    {
        $search = array('Ș', 'Ț', 'ş', 'ţ', 'Ş', 'Ţ', 'ș', 'ț', 'î', 'â', 'ă', 'Î', ' ', 'Ă', 'ë', 'Ë','ä','Ä','ü','Ü','ö','Ö','ß');
        $replace = array('s', 't', 's', 't', 's', 't', 's', 't', 'i', 'a', 'a', 'i','_', 'a', 'a', 'e', 'E','ae','Ae','ue','Ue','oe','Oe','ss');
        $str = str_ireplace($search, $replace, strtolower(trim($urlString)));
        $str = preg_replace('/[^\w\-\ ]/', '', $str);
        $str = str_replace(' ', '-', $str);
        $slug = preg_replace('/\-{2,}/', '-', $str);
        return $slug;
    }
}