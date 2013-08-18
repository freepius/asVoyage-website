<?php

namespace App\Model\Factory;

use Symfony\Component\Validator\Constraints as Assert,
    Symfony\Component\Validator\ValidatorInterface,
    App\Util\CaptchaManager,
    App\Util\StringUtil;


class Contact extends EntityFactory
{
    protected $captcha;

    public function __construct(ValidatorInterface $validator, CaptchaManager $captcha)
    {
        parent::__construct($validator);
        $this->captcha = $captcha;
    }

    /**
     * Add a captcha to the mail.
     */
    public function addCaptcha(array & $entity)
    {
        $entity['captcha'] = $this->captcha->getFilename();
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
            'name'    => '',
            'email'   => '',
            'subject' => '',
            'message' => '',
        ];
    }

    /**
     * @{inheritdoc}
     */
    protected function processInputData(array $data)
    {
        return [
            'name'    => trim($data['name']),
            'email'   => trim($data['email']),
            'subject' => trim($data['subject']),
            'message' => StringUtil::cleanText($data['message']),
            'captcha' => $this->captcha->isValid((string) $data['captcha']),
        ];
    }

    /**
     * @{inheritdoc}
     */
    protected function getConstraints(array $entity)
    {
        return new Assert\Collection([
            'name'    => new Assert\NotBlank(),
            'email'   => [new Assert\NotBlank(), new Assert\Email()],
            'subject' => new Assert\NotBlank(),
            'message' => new Assert\NotBlank(),
            'captcha' => new Assert\True(['message' => 'This value is not valid.']),
        ]);
    }
}
