<?php

namespace App\Util;

use Michelf\MarkdownExtra,
    Michelf\SmartyPantsTypographer;


/**
 * Transform a text using MarkdownExtra and SmartyPantsTypographer
 */
class RichText
{
    const SCRIPT_TAG_PATTERN = '{<(\s*)script(.*)>.*<(\s*)/(\s*)script(.*)>}si';

    public $markdown;
    public $smartypants;

    public function __construct($locale = 'en')
    {
        $this->markdown = new MarkdownExtra();
        $this->smartypants = new SmartyPantsTypographer('qgD:+;+m+h+H+f+u+t');

        // HTML output
        $this->markdown->empty_element_suffix = ">";

        if ('fr' === $locale)
        {
            // French quotes
            $this->smartypants->smart_doublequote_open  = '&#171;';
            $this->smartypants->smart_doublequote_close = '&#187;';
        }
    }

    public function transform($text)
    {
        // avoid conflicts for footnote ids
        $this->markdown->fn_id_prefix = uniqid();

        $text = $this->markdown->transform($text);

        $text = $this->smartypants->transform($text);

        // remove <script> tags
        $text = preg_replace(self::SCRIPT_TAG_PATTERN, '', $text);

        return $text;
    }
}
