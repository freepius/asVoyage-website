<?php

namespace App\Util;


/**
 * Utilities to group elements depending on various criteria.
 */
class Group
{
    /**
     * Return an array, "natural sorted" by keys, whose :
     *    Key   = a unique tag
     *    Value = [0 => occurrences of it ; 1 => its percentage on elements number]
     *
     * Each element of $collection must :
     *  -> be an array or \ArrayAccess
     *  -> have an array indexed by the 'tags' key / offset
     */
    public static function byTags(\Iterator $collection)
    {
        $total  = 0;
        $result = [];

        // Retrieve all tags + their occurrences
        foreach ($collection as $element)
        {
            $tags = $element['tags'];
            $total++;

            foreach ($tags as $tag)
            {
                $occ = & $result[$tag];
                $occ++;
            }
        }

        // Compute the percentage of each
        $result = array_map(function ($nOcc) use ($total)
        {
            return [$nOcc, (int) round($nOcc / $total * 100)];
        },
        $result);

        // "Natural sort" by keys
        uksort($result, '\strnatcmp');

        return $result;
    }

    /**
     * Return a two-dimensional array whose :
     *    Key   = a year
     *    Value = { a month (1 to 12) : number of elements on this "year-month" }+
     *
     * Each element of $collection must :
     *  -> be an array or \ArrayAccess
     *  -> have an array indexed by the $field key / offset
     */
    public static function byYearMonth(\Iterator $collection, $field)
    {
        $result = [];

        foreach ($collection as $element)
        {
            $year  = (int) substr($element[$field], 0, 4);
            $month = (int) substr($element[$field], 5, 2);

            $occ = & $result[$year][$month];
            $occ++;
        }

        // Sort by year DESC
        krsort($result);

        // Sort by month DESC
        foreach ($result as & $year) { krsort($year); }

        return $result;
    }
}
