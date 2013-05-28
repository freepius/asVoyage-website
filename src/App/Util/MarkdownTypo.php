<?php

namespace App\Util;

use Michelf\MarkdownExtra,
    michelf\SmartyPantsTypographer;


/**
 * Transform a text using MarkdownExtra and SmartyPantsTypo
 */
class MarkdownTypo
{
    public $markdown;
    public $smartypants;

    public function __construct()
    {
        $this->markdown = new MarkdownExtra();
        $this->smartypants = @ new SmartyPantsTypographer('qgD:+;+m+h+H+f+u+t');

        // French quotes
        $this->smartypants->smart_doublequote_open  = '&#171;';
        $this->smartypants->smart_doublequote_close = '&#187;';
    }

    public function transform($text)
    {
        return $this->smartypants->transform(
            $this->markdown->transform($text)
        );
    }
}
