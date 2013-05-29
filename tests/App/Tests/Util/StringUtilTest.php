<?php

use App\Util\StringUtil;


class StringUtilTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerSlugify
     */
    public function testSlugify($slug, $text)
    {
        setlocale(LC_ALL, 'fr');

        $this->assertEquals($slug, StringUtil::slugify($text));
    }

    public function providerSlugify()
    {
        return array(
            array("",             " "),
            array("efooe",        "éFooè"),
            array("efooe",        "efooe"),
            array("oeae-cui-oae", "œæ çûî/\ôÂÊ"),
            array("bac-a-sable",  "Bac à sable")
            // TODO: add other tests
        );
    }
}
