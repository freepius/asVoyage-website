<?php

namespace App\Model\Factory;

use Symfony\Component\Validator\Constraints as Assert,
    Symfony\Component\Validator\ValidatorInterface,
    Symfony\Component\HttpFoundation\File\File,
    Gregwar\Image\Image,
    App\Util\Geo;


class MediaUploaded extends EntityFactory
{
    protected $config;
    protected $webPath;

    public function __construct(ValidatorInterface $validator, $webPath, array $config)
    {
        parent::__construct($validator);
        $this->webPath = $webPath;
        $this->config = $config;
    }

    /**
     * From the $file MIME type, retrieve main type (eg: 'audio') and sub type (eg: 'ogg').
     */
    protected function getTypes(File $file)
    {
        list($main, $sub) = explode('/', $file->getMimeType());

        // Transform all "ogg" types to "audio/ogg"
        if (in_array($main, ['application', 'audio']) &&
            in_array($sub,  ['oga', 'ogg', 'ogx'])
        ) {
            $main = 'audio';
            $sub  = 'ogg';
        }

        return [$main, $sub];
    }

    /**
     * @{inheritdoc}
     */
    public function instantiate()
    {
        return [
            'caption'      => '',
            'creationDate' => '',
            'geoCoords'    => '',
            'tags'         => '',
        ];
    }

    /**
     * @{inheritdoc}
     */
    protected function processInputData(array $data)
    {
        // Retrieve the first file
        $file = $data[0];

        // Get types (eg: $mainType = 'image', $subType = 'jpeg')
        list($mainType, $subType) = $this->getTypes($file);

        // Create a unique filename
        $fileName = uniqid() .'.'. $file->guessExtension();

        return [
            'content'      => $fileName,
            'originalName' => $file->getClientOriginalName(),
            'size'         => $file->getSize(),
            'mainType'     => $mainType,
            'subType'      => $subType,
            'file'         => $file->move($this->webPath.'/media', $fileName),
        ];
    }

    /**
     * @{inheritdoc}
     */
    protected function getConstraints(array $entity)
    {
        return new Assert\Collection(
        [
            'fields' => [
                'file'=> new Assert\File([
                    'maxSize'   => $this->config['maxFileSize'],
                    'mimeTypes' => $this->config['acceptTypes.mime'],
                ]),
            ],
            'allowExtraFields' => true,
        ]);
    }

    /**
     * @{inheritdoc}
     */
    public function bind(array & $entity, array $inputData)
    {
        $errors = parent::bind($entity, $inputData);

        if ($errors)
        {
            $entity['error'] = $errors['file'];

            unlink($entity['file']->getPathname());
        }
        elseif ('image' === $entity['mainType'])
        {
            $this->bindImage($entity);
        }

        unset($entity['file']);

        return $errors;
    }

    /**
     * Process an image during the uploading step :
     *  -> create a reduction for a web usage (or a link if original is too small)
     *  -> create a thumbnail                 (idem)
     *  -> extract EXIF metadata (for jpeg only) : creation date and geo. coords
     */
    protected function bindImage(array & $entity)
    {
        $thumbSize = $this->config['image.thumb.size'];
        $webSize   = $this->config['image.web.size'];
        $fileName  = $entity['content'];
        $imageType = $entity['subType'];

        $filePath  = $entity['file']->getPathname();
        $webPath   = "{$this->webPath}/media/web/$fileName";
        $thumbPath = "{$this->webPath}/media/thumb/$fileName";

        // Load the original image
        $original = Image::open($filePath);

        // Store width and height
        $entity['width']  = $width  = $original->width();
        $entity['height'] = $height = $original->height();

        // Small side of original image
        $smallSize     = min($width, $height);
        $small_isWidth = $width < $height;

        // Create web image from original
        if ($smallSize > $webSize)
        {
            $original
                ->cropResize(
                    $small_isWidth ? $webSize : null,
                    $small_isWidth ? null     : $webSize
                )
                ->save($webPath, 'guess', 90);
        }
        else {
            symlink("../$fileName", $webPath);
        }

        // Create thumbnail from web image (faster than from original)
        if ($smallSize > $thumbSize)
        {
            Image::open($webPath)
                ->cropResize(
                    $small_isWidth ? $thumbSize : null,
                    $small_isWidth ? null       : $thumbSize
                )
                ->save($thumbPath, 'guess', 80);
        }
        else {
            symlink("../$fileName", $thumbPath);
        }

        // If jpeg => extract EXIF metadata
        if ('jpeg' === $imageType)
        {
            $exif = exif_read_data($filePath);

            $entity['creationDate'] = (! @ $exif['DateTimeOriginal']) ? null :
            date(
                'Y-m-d H:i:s',
                strtotime($exif['DateTimeOriginal'])
            );

            $entity['geoCoords'] = Geo::exif2decimal($exif);
        }
    }
}
