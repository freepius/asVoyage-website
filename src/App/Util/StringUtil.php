<?php

namespace App\Util;


class StringUtil
{
    const LOCAL = 'fr_FR.UTF-8';

    /**
     * Modifies a string to remove all non ASCII characters and spaces,
     * and to put ASCII characters in lowercase.
     */
    public static function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

        // trim
        $text = trim($text, '-');

        // transliterate
        if (function_exists('iconv'))
        {
            setlocale(LC_ALL, self::LOCAL); // Alter //TRANSLIT result
            $text = iconv('utf-8', 'ascii//TRANSLIT', $text);
        }

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        return strtolower($text);
    }
}
