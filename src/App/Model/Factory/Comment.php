<?php

namespace App\Model\Factory;

use Symfony\Component\Validator\Constraints as Assert,
    Symfony\Component\Validator\ValidatorInterface,
    Symfony\Component\Security\Core\SecurityContextInterface,
    App\Util\CaptchaManager;


class Comment extends EntityFactory
{
    protected $security;
    protected $captcha;

    public function __construct(ValidatorInterface $validator, SecurityContextInterface $security, CaptchaManager $captcha)
    {
        parent::__construct($validator);
        $this->security = $security;
        $this->captcha = $captcha;
    }

    protected static function cleanText($text)
    {
        $text = trim($text);
        $text = preg_replace('/\r\n?/' , "\n"    , $text);  // unix nl
        $text = preg_replace('/\n{4,}/', "\n\n\n", $text);  // max. 3 nl

        return $text;
    }

    /**
     * The current "comment context" needs a captcha ?
     */
    protected function needCaptcha()
    {
        return ! $this->security->isGranted('IS_AUTHENTICATED_FULLY');
    }

    /**
     * If needed, add a captcha to the comment.
     */
    public function addCaptchaIfNeeded(array & $entity)
    {
        if ($this->needCaptcha()) {
            $entity['captcha'] = $this->captcha->getFilename();
        }
    }

    protected function merge(array $entity, array $inputData)
    {
        unset($inputData['captcha']);
        return parent::merge($entity, $inputData);
    }

    /**
     * @{inheritdoc}
     */
    public function instantiate()
    {
        return [
            'name'     => '',
            'text'     => '',
            'datetime' => '',
        ];
    }

    /**
     * @{inheritdoc}
     */
    protected function processInputData(array $data)
    {
        return [
            'name'     => trim($data['name']),
            'text'     => self::cleanText($data['text']),
            'datetime' => date('Y-m-d H:i:s'), // now
            'captcha'  => $this->needCaptcha() ?
                          $this->captcha->isValid((string) @ $data['captcha']) : true,
        ];
    }

    /**
     * @{inheritdoc}
     */
    protected function getConstraints(array $entity)
    {
        return new Assert\Collection([
            'name'     => new Assert\NotBlank(),
            'text'     => new Assert\NotBlank(),
            'datetime' => new Assert\DateTime(),
            'captcha'  => new Assert\True(['message' => 'This value is not valid.']),
        ]);
    }
}
