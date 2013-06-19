<?php

namespace App\Util;

use Symfony\Component\HttpFoundation\Session\SessionInterface,
    Gregwar\Captcha\CaptchaBuilder,
    Gregwar\Captcha\ImageFileHandler;


class CaptchaManager
{
    const SESSION_KEY = 'captcha';

    const IMAGE_FOLDER = 'images/captcha';

    protected $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * Generate the captcha as a jpg image, and return its filename
     * (relative to the web dir).
     */
    public function getFilename()
    {
        $imageFileHandler = new ImageFileHandler(self::IMAGE_FOLDER, WEB, 20, 60);

        // Randomly execute garbage collection
        $imageFileHandler->collectGarbage();

        // If a captcha already exists => return it directly
        if (($captcha = $this->session->get(self::SESSION_KEY))
            && ($filename = @ $captcha['filename'])
            && file_exists(WEB .'/'. $filename))
        {
            touch(WEB .'/'. $filename); // update modification date

            return $filename;
        }

        // Generate a captcha
        $builder = new CaptchaBuilder();
        $content = $builder->build(225, 60)->getGd();

        // Save it in session
        $this->session->set(self::SESSION_KEY, array
        (
            'phrase'   => $builder->getPhrase(),
            'filename' => $filename = $imageFileHandler->saveAsFile($content),
        ));

        return $filename;
    }

    /**
     * Test if a given phrase is valid (ie: same as in session).
     */
    public function isValid($phrase)
    {
        $captcha = $this->session->get(self::SESSION_KEY);

        $builder = new CaptchaBuilder(@ $captcha['phrase']);

        return is_string($phrase) && $builder->testPhrase($phrase);
    }
}
