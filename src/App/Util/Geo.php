<?php

namespace App\Util;


class Geo
{
    /**
     * Transform a geo. coordinate from EXIF format to decimal format.
     * EXIF format is :
     *  -> $dir = 'E' or 'W' for longitude ; 'N' or 'S' for latitude.
     *  -> $dms =
     *  [
     *      0 => degrees as a fraction (eg: "47/1")
     *      1 => minutes as a fraction (eg: "20/1")
     *      2 => seconds as a fraction (eg: "16176/1000")
     *  ]
     */
    public static function oneExif2decimal($dir, array $dms)
    {
        foreach ($dms as & $fraction)
        {
            $part = explode('/', $fraction);

            $fraction = intval($part[0]) / max(1, intval($part[1]));
        }

        return (($dir === 'W' || $dir === 'S') ? -1 : 1) *
               ($dms[0] + $dms[1] / 60 + $dms[2] / 3600);
    }

    /**
     * Transform the geo. coordinates from EXIF format to decimal format.
     * @see oneExif2decimal()
     */
    public static function exif2decimal(array $exif)
    {
        if (! @ $exif['GPSLatitudeRef'] || ! @ $exif['GPSLongitudeRef'])
        {
            return '';
        }

        return sprintf(
            "%F, %F",
            self::oneExif2decimal($exif['GPSLatitudeRef'],  $exif['GPSLatitude']),
            self::oneExif2decimal($exif['GPSLongitudeRef'], $exif['GPSLongitude'])
        );
    }
}
