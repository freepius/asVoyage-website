<?php

namespace App\Util;

use Symfony\Component\HttpFoundation\Session\SessionInterface,
    Gregwar\Captcha\CaptchaBuilder,
    Gregwar\Captcha\ImageFileHandler;


class CaptchaManager
{
    protected $session;

    // Config parameters
    protected $sessionKey;
    protected $webPath;
    protected $imageFolder;

    public function __construct(SessionInterface $session, array $config = [])
    {
        $this->session = $session;

        // Set config parameters
        $this->sessionKey = @ $config['sessionKey'] ?: 'captcha';

        $this->webPath = @ $config['webPath'] ?: __DIR__.'/../../../web';

        $this->imageFolder = @ $config['imageFolder'] ?: 'images/captcha';
    }

    /**
     * Generate a captcha as jpg image, and return its filename
     * (relative to the WEB directory).
     * If a captcha already exists in session, return its filename directly.
     */
    public function getFilename()
    {
        $imageFileHandler = new ImageFileHandler($this->imageFolder, $this->webPath, 20, 60);

        // Randomly execute garbage collection
        $imageFileHandler->collectGarbage();

        // If a captcha already exists => return it directly
        if (($captcha = $this->session->get($this->sessionKey))
            && ($filename = @ $captcha['filename'])
            && file_exists($this->webPath .'/'. $filename))
        {
            touch($this->webPath .'/'. $filename); // update modification date

            return $filename;
        }

        // Generate a captcha
        $builder = new CaptchaBuilder();
        $content = $builder->build(225, 60)->getGd();

        // Save it in session
        $this->session->set($this->sessionKey,
        [
            'phrase'   => $builder->getPhrase(),
            'filename' => $filename = $imageFileHandler->saveAsFile($content),
        ]);

        return $filename;
    }

    /**
     * Test if a given phrase is valid (ie: same as in session).
     */
    public function isValid($phrase)
    {
        $captcha = $this->session->get($this->sessionKey);

        $builder = new CaptchaBuilder(@ $captcha['phrase']);

        return is_string($phrase) && $builder->testPhrase($phrase);
    }

    /**
     * Revoke the eventual captcha from current session.
     */
    public function revoke()
    {
        $this->session->remove($this->sessionKey);
    }
}
