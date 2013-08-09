<?php

namespace App\Util;


class StringUtil
{
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
            $text = iconv('utf-8', 'ascii//TRANSLIT', $text);
        }

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        return strtolower($text);
    }

    /**
     * @param  string  $tags  Tags separated by ','
     *
     * Return an array (without duplicates) of tags as strings (non-empty, natural sorted).
     */
    public static function normalizeTags($tags)
    {
        // A tag is lowercase ; its first letter us uppercase
        $processTag = function ($tag) {
            return ucfirst(strtolower(trim($tag)));
        };

        $tags = explode(',', $tags);

        // No blank value
        $tags = array_filter(array_map($processTag, $tags));

        natsort($tags);

        // Remove duplicates + rearrange
        return array_values(array_unique($tags));
    }
}
